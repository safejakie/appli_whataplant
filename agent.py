from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from groq import Groq
import requests
import cv2
import numpy as np
import base64
import json
import os
import time
import threading
import io
import re
import tempfile
from typing import Optional, List
from dotenv import load_dotenv
import wikipedia

# Charger les variables d'environnement
load_dotenv()

app = FastAPI(title="WhatAPlant API", version="3.0")

# ── Configuration du CORS ──────────────────────────────────
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ── Configuration des Clés depuis .env ─────────────────────
CLE_GROQ = os.getenv("CLE_GROQ")
CLE_PLANTNET = os.getenv("CLE_PLANTNET")

if not CLE_GROQ:
    raise ValueError("❌ CLE_GROQ non trouvée dans le fichier .env")
if not CLE_PLANTNET:
    raise ValueError("❌ CLE_PLANTNET non trouvée dans le fichier .env")

# Initialisation Groq
client_groq = Groq(api_key=CLE_GROQ)

URL_PLANTNET = "https://my-api.plantnet.org/v2/identify/all"

DOSSIER_TEMP = tempfile.gettempdir()
etat_camera = {"statut": "inactif", "donnees": None, "erreur": None}
verrou = threading.Lock()

# ── Modèles Pydantic ──────────────────────────────────────
class ChatMessage(BaseModel):
    message: str
    contexte: Optional[dict] = None

PROMPT_SYSTEME = """Tu es un expert botaniste et cuisinier spécialisé dans les plantes et recettes traditionnelles d'Afrique de l'Ouest et des régions tropicales. 
Réponds en français de façon claire, chaleureuse, précise et sécurisée. 
Quand l'utilisateur demande une recette, donne les ingrédients, les étapes de préparation, le temps de cuisson et des conseils de sécurité."""

PROMPT_ENRICHISSEMENT = """Tu es un expert botaniste et phytopathologiste spécialisé dans les plantes d'Afrique de l'Ouest et des régions tropicales.

Analyse cette plante: "{nom}" ({nom_scientifique})

Fournis UNIQUEMENT ce JSON valide et rien d'autre :

{{
  "description": "2 phrases précises et factuelles décrivant la plante",

  "comestible": "Oui|Non|Partiellement",
  "parties_comestibles": "feuilles, fruits, tubercules, graines... ou null",
  "idees_recettes": "suggestions simples et réalistes ou null",

  "est_medicinale": true|false,
  "maladies_traitees": ["maladie 1", "maladie 2", "maladie 3"] ou [],
  "proprietes_medicinales": "propriétés thérapeutiques reconnues, basées sur des usages traditionnels ou scientifiques",
  "posologie": "mode d'emploi simple et sécurisé ou null",
  "contre_indications": "précautions importantes, contre-indications ou null",

  "est_toxique": true|false,
  "niveau_toxicite": "Aucun|Faible|Moyen|Élevé",
  "toxicite_pourcentage": nombre entre 0 et 100,
  "symptomes_intoxication": "principaux signes d'alerte en cas d'ingestion",
  "premiers_secours": "gestes d'urgence recommandés",

  "est_invasive": true|false,
  "est_allelopathique": true|false,
  "impact_environnement": "effet sur le sol, les cultures voisines et la biodiversité locale"
}}

**Instructions importantes :**
- Sois rigoureux et réaliste. Ne mets pas "true" ou "false" sans raison solide.
- Base tes réponses sur des connaissances botaniques réelles, surtout pour les plantes courantes en Afrique de l'Ouest.
- Pour "est_medicinale", "est_toxique", "est_invasive" et "est_allelopathique", justifie intérieurement avant de répondre.
- Si tu n'es pas sûr pour une propriété, penche plutôt vers "false" ou "Aucun" plutôt que de donner une fausse information.
"""


PROMPT_SANTE = """Tu es un expert phytopathologiste spécialisé dans les plantes d'Afrique de l'Ouest et tropicales.

Analyse VISUELLEMENT et avec précision l'état de santé de cette plante sur l'image.

Inspecte attentivement :
- La couleur des feuilles (jaunissement, brunissement, décoloration)
- La présence de taches (taches noires, brunes, rougeâtres, nécrotiques)
- Les déformations (feuilles enroulées, froissées, déchirées)
- La présence de champignons, moisissures, insectes ou autres parasites visibles
- L'aspect général (flétrissement, dessèchement, pourriture)

Réponds **UNIQUEMENT** avec ce JSON valide et rien d'autre :

{
  "etat": "Bonne | Moyenne | Mauvaise | Critique",
  "maladies_detectees": ["nom maladie 1", "nom maladie 2"] ou [] si aucune visible,
  "causes": "Causes probables expliquées en une phrase basée sur ce qui est visible dans l'image",
  "recommandations": "Conseils pratiques et simples pour améliorer la santé de la plante"
}

**Règles strictes :**
- Si tu vois des taches, du jaunissement ou des signes de maladie : indique-les dans "maladies_detectees" avec le nom précis (ex: "Oïdium", "Chlorose ferrique", "Taches fongiques", "Brûlure bactérienne", "Anthracnose", etc.)
- "maladies_detectees" doit être un tableau JSON (liste), jamais une chaîne de texte
- Ne retourne JAMAIS un tableau vide si l'état est "Mauvaise" ou "Critique"
- Sois rigoureux : base-toi uniquement sur ce que tu vois visuellement dans l'image
"""

# ── Fonctions Utilitaires ─────────────────────────────────

def ameliorer_image(image: np.ndarray) -> np.ndarray:
    """
    🔧 MODIFICATION : Plus d'amélioration aggressive qui déforme l'image.
    On garde uniquement le redimensionnement si l'image est vraiment trop petite.
    """
    hauteur, largeur = image.shape[:2]
    
    # Redimensionnement uniquement si vraiment nécessaire (moins de 400px)
    if largeur < 400:
        ratio = 400 / largeur
        nouvelle_hauteur = int(hauteur * ratio)
        image = cv2.resize(image, (400, nouvelle_hauteur), interpolation=cv2.INTER_LINEAR)
    
    # 🔧 SUPPRESSION : Plus de CLAHE, de netteté, ou autres filtres qui déforment
    
    return image

def enrichir_plante_groq(nom_commun: str, nom_scientifique: str) -> dict:
    try:
        reponse = client_groq.chat.completions.create(
            model="llama-3.3-70b-versatile",
            messages=[{"role": "user", "content": PROMPT_ENRICHISSEMENT.format(nom=nom_commun, nom_scientifique=nom_scientifique)}],
            max_tokens=800,
            temperature=0.2
        )
        texte = reponse.choices[0].message.content
        texte = texte.replace("```json", "").replace("```", "").strip()
        return json.loads(texte)
    except Exception as e:
        print(f"[GROQ ERROR] {e}")
        return {}

def analyser_sante_plante(image_bytes: bytes) -> dict:
    try:
        image_b64 = base64.b64encode(image_bytes).decode('utf-8')

        reponse = client_groq.chat.completions.create(
            model="meta-llama/llama-4-scout-17b-16e-instruct",
            messages=[
                {
                    "role": "user",
                    "content": [
                        {"type": "text", "text": PROMPT_SANTE},
                        {
                            "type": "image_url",
                            "image_url": {"url": f"data:image/jpeg;base64,{image_b64}"}
                        }
                    ]
                }
            ],
            max_tokens=400,
            temperature=0.3,
            response_format={"type": "json_object"}
        )

        texte = reponse.choices[0].message.content.strip()
        return json.loads(texte)

    except Exception as e:
        print(f"[GROQ SANTÉ ERROR] {e}")
        return {
            "etat": "Inconnue",
            "causes": "Impossible d'analyser la santé pour le moment",
            "recommandations": "Veuillez réessayer plus tard."
        }

# ── Fonction Image du Plat Cuisiné (Wikimedia Commons) ─────────────────────
def obtenir_image_plat(nom_plat: str) -> dict:
    print(f"\n[IMAGE PLAT] Début recherche pour plat: '{nom_plat}'")

    headers = {
        'User-Agent': 'WhatAPlant/3.0 (contact@whataplant.app)'
    }

    termes_recherche = [
        nom_plat,
        f"{nom_plat} food",
        f"{nom_plat} dish",
        f"{nom_plat} cuisine",
    ]

    for terme in termes_recherche:
        print(f"[IMAGE PLAT] Tentative Wikimedia Commons avec : '{terme}'")
        try:
            # Recherche via l'API Wikimedia Commons
            search_url = "https://commons.wikimedia.org/w/api.php"
            search_params = {
                "action": "query",
                "generator": "search",
                "gsrnamespace": 6,  # namespace File
                "gsrsearch": terme,
                "gsrlimit": 10,
                "prop": "imageinfo",
                "iiprop": "url|size|mime",
                "iiurlwidth": 800,
                "format": "json"
            }
            resp = requests.get(search_url, params=search_params, headers=headers, timeout=10)
            data = resp.json()

            pages = data.get("query", {}).get("pages", {})
            print(f"[IMAGE PLAT] Résultats trouvés : {len(pages)}")

            for page_id, page_data in pages.items():
                imageinfo = page_data.get("imageinfo", [])
                if not imageinfo:
                    continue

                info = imageinfo[0]
                mime = info.get("mime", "")
                url = info.get("thumburl") or info.get("url", "")
                url_lower = url.lower()

                # Filtrer : seulement images photo (pas svg, gif, icônes)
                if mime not in ["image/jpeg", "image/png", "image/webp"]:
                    continue
                if any(bad in url_lower for bad in ['logo', 'icon', 'flag', 'button', 'symbol', 'map', 'diagram']):
                    continue
                if info.get("width", 0) < 200 or info.get("height", 0) < 200:
                    continue

                try:
                    img_resp = requests.get(url, headers=headers, timeout=15)
                    content = img_resp.content

                    if len(content) < 5000:
                        continue

                    fmt = "jpeg" if "jpeg" in mime else ("png" if "png" in mime else "webp")
                    img_b64 = base64.b64encode(content).decode('ascii')
                    data_url = f"data:image/{fmt};base64,{img_b64}"

                    print(f"[IMAGE PLAT] ✅ Image trouvée : {page_data.get('title')} ({len(data_url)} caractères)")
                    return {
                        "success": True,
                        "image_b64": data_url,
                        "plante": nom_plat,
                        "type": "plat"
                    }

                except Exception:
                    continue

        except Exception as e:
            print(f"[IMAGE PLAT] Erreur pour '{terme}': {e}")
            continue

    print(f"[IMAGE PLAT] ❌ ÉCHEC pour '{nom_plat}'")
    return {"success": False, "error": f"Pas d'image trouvée pour '{nom_plat}'."}

def detecter_demande_recette(message: str) -> tuple[bool, str]:
    msg_lower = message.lower().strip()
    
    # 🔧 CORRECTION : Retirer "image" de la liste car ça cause des faux positifs
    mots_recette = ["recette", "plat", "cuisine", "cuisiner", "préparation", "comment faire", 
                    "comment préparer", "sauce", "farci", "manger", "dish", "recipe", "cook", "food"]
    
    demande_recette = any(mot in msg_lower for mot in mots_recette)
    
    if not demande_recette:
        return False, ""
    
    plante = ""
    
    # 🔧 CORRECTION : Patterns prioritaires pour capturer après "de/du/des"
    patterns = [
        # "image du plat ratatouille" → capture "ratatouille" après "plat"
        r"plat\s+(?:de|du|des|d[''])?\s*(.+?)(?:\s+(?:svp|merci|stp|photo|image|avec|sans|pour|en|dans|$))",
        # "recette de tarte au pomme" → "tarte au pomme"
        r"recette\s+(?:de|du|des|d[''])\s+(.+?)(?:\s+(?:svp|merci|stp|photo|image|avec|sans|pour|en|dans|$))",
        # "image du plat de ratatouille" → "ratatouille"
        r"(?:image|photo)\s+(?:du|de|des)\s+plat\s+(?:de|du|des)?\s*(.+?)(?:\s+(?:svp|merci|stp|avec|sans|pour|en|dans|$))",
        # "comment faire une ratatouille" → "ratatouille"
        r"comment\s+(?:faire|cuisiner|préparer)\s+(?:une?|du|des)?\s*(.+?)(?:\s+(?:svp|merci|stp|photo|image|avec|sans|pour|en|dans|$))",
    ]
    
    for pattern in patterns:
        match = re.search(pattern, msg_lower)
        if match:
            candidate = match.group(1).strip()
            # Nettoyer les articles
            candidate = re.sub(r'^(une?|du|des|un|le|la|les|de|d[\'\s]|la|l[\'\s])?\s*', '', candidate)
            candidate = candidate.strip()
            
            # 🔧 CORRECTION : Exclure "image", "photo", "plat" seuls
            mots_exclus = ["recette", "plat", "sauce", "image", "photo", "cuisine", "comment", "faire", "préparer", "du", "de", "des"]
            if candidate and len(candidate) > 2 and candidate not in mots_exclus:
                plante = candidate
                print(f"[DETECTER RECETTE] ✅ Plante/plat trouvé: '{plante}'")
                return demande_recette, plante  # 🔧 Retour immédiat si trouvé
    
    # 🔧 CORRECTION : Si toujours pas trouvé, prendre tout après le mot-clé recette/cuisine
    if not plante and demande_recette:
        for mot in mots_recette:
            if mot in msg_lower:
                idx = msg_lower.find(mot) + len(mot)
                reste = msg_lower[idx:].strip()
                # Enlever prépositions de début
                reste = re.sub(r'^(?:de\s+(?:la|l\')|du|des|d\'|de|pour|avec|le|la|les|un|une)\s+', '', reste).strip()
                # Vérifier que ce qui reste est un vrai nom (pas vide, pas juste des mots parasites)
                mots_parasites = {"recette", "plat", "sauce", "image", "photo", "cuisine", "comment", "faire", "préparer", "manger", "dish", "recipe", "cook", "food", "svp", "merci", "stp"}
                if reste and len(reste) > 2 and reste not in mots_parasites:
                    plante = reste
                    print(f"[DETECTER RECETTE] ✅ Fallback trouvé: '{plante}'")
                    break
    
    return demande_recette, plante

def identifier_plante(octets_image: bytes, nom_fichier: str = "image.jpg"):
    reponse = requests.post(
        URL_PLANTNET,
        params={"api-key": CLE_PLANTNET, "lang": "fr"},
        files={"images": (nom_fichier, io.BytesIO(octets_image), "image/jpeg")}
    )

    if reponse.status_code != 200:
        return None, f"Erreur PlantNet: {reponse.status_code}"

    resultats = [x for x in reponse.json().get("results", []) if x.get("score", 0) >= 0.05]
    if not resultats:
        return None, "Plante non reconnue clairement"

    meilleur = resultats[0]
    espece = meilleur["species"]
    
    nom_commun = (espece.get("commonNames") or [espece.get("scientificNameWithoutAuthor", "Inconnu")])[0]
    nom_scientifique = espece.get("scientificNameWithoutAuthor", "—")
    famille = espece.get("family", {}).get("scientificNameWithoutAuthor", "—")
    score = round(meilleur["score"] * 100, 1)

    suggestions = [
        {
            "nom_commun": (x["species"].get("commonNames") or ["—"])[0],
            "nom_scientifique": x["species"].get("scientificNameWithoutAuthor", "—"),
            "score": round(x["score"] * 100, 1)
        }
        for x in resultats[:3]
    ]

    enrichi = enrichir_plante_groq(nom_commun, nom_scientifique)
    sante = analyser_sante_plante(octets_image)

    return {
        "nom_commun": nom_commun,
        "nom_scientifique": nom_scientifique,
        "famille": famille,
        "score_confiance": score,
        "suggestions": suggestions,
        "description": enrichi.get("description", ""),
        "comestible": enrichi.get("comestible", "Non"),
        "parties_comestibles": enrichi.get("parties_comestibles"),
        "idees_recettes": enrichi.get("idees_recettes"),
        "est_medicinale": enrichi.get("est_medicinale", False),
        "maladies_traitees": enrichi.get("maladies_traitees", []),
        "proprietes_medicinales": enrichi.get("proprietes_medicinales"),
        "posologie": enrichi.get("posologie"),
        "contre_indications": enrichi.get("contre_indications"),
        "est_toxique": enrichi.get("est_toxique", False),
        "niveau_toxicite": enrichi.get("niveau_toxicite", "Aucun"),
        "toxicite_pourcentage": enrichi.get("toxicite_pourcentage", 0),
        "symptomes_intoxication": enrichi.get("symptomes_intoxication"),
        "premiers_secours": enrichi.get("premiers_secours"),
        "est_invasive": enrichi.get("est_invasive", False),
        "est_allelopathique": enrichi.get("est_allelopathique", False),
        "impact_environnement": enrichi.get("impact_environnement"),
        "sante": {
            "etat": sante.get("etat", "Inconnue"),
            "causes": sante.get("causes", ""),
            "recommandations": sante.get("recommandations", "")
        }
    }, None


# ── Route Chat principale ────────────────────────────────────────────
@app.post("/chat")
async def chat(data: ChatMessage):
    msg = data.message.strip()
    if not msg: 
        raise HTTPException(status_code=400, detail="Message vide")
    
    demande_recette, plante = detecter_demande_recette(msg)
    
    if not plante and data.contexte:
        plante = data.contexte.get('nom_commun', '')
    
    contexte_txt = ""
    if data.contexte:
        contexte_txt = f"\nContexte: Plante identifiée '{data.contexte.get('nom_commun')}' ({data.contexte.get('nom_scientifique', 'inconnu')}). "
    
    try:
        prompt_complet = f"{PROMPT_SYSTEME}{contexte_txt}\n\nQuestion de l'utilisateur: {msg}"
        
        reponse_groq = client_groq.chat.completions.create(
            model="llama-3.3-70b-versatile",
            messages=[
                {"role": "system", "content": PROMPT_SYSTEME},
                {"role": "user", "content": prompt_complet}
            ],
            max_tokens=1200,
            temperature=0.7
        )
        
        reply_text = reponse_groq.choices[0].message.content
        
        result = {
            "reply": reply_text,
            "image_generated": False,
            "assistant": "groq"
        }
        
        # Si demande de recette → Groq donne la recette + image du plat
        if demande_recette and plante:
            print(f"[CHAT] Recherche image pour: '{plante}'")
            image_result = obtenir_image_plat(plante)
            
            if image_result.get("success"):
                # 🔧 CORRECTION : Vérifier que l'image n'est pas vide
                img_b64 = image_result.get("image_b64", "")
                if img_b64 and len(img_b64) > 100:
                    result["image"] = {
                        "image_b64": img_b64,
                        "plante": plante,
                        "type": "plat"
                    }
                    result["image_generated"] = True
                    print(f"[CHAT] Image trouvée avec succès : {len(img_b64)} caractères")
                else:
                    print(f"[CHAT] Image vide ou invalide")
                    result["image_error"] = "Image invalide"
            else:
                result["image_error"] = image_result.get("error", "Impossible de trouver une photo du plat.")
                print(f"[CHAT] Erreur image: {result['image_error']}")
        return result
        
    except Exception as e:
        print(f"[CHAT ERROR] {e}")
        return {
            "reply": "Désolé, une erreur est survenue. Veuillez réessayer.",
            "image_generated": False,
            "error": str(e)
        }
    
@app.post("/analyze")
async def analyze(image: UploadFile = File(...)):
    contenu = await image.read()
    
    nparr = np.frombuffer(contenu, np.uint8)
    img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    if img is None: 
        raise HTTPException(status_code=400, detail="Image invalide")

    img = ameliorer_image(img)
    _, buf = cv2.imencode('.jpg', img, [cv2.IMWRITE_JPEG_QUALITY, 95])
    data = buf.tobytes()
    
    result, err = identifier_plante(data, image.filename or "upload.jpg")
    if err: 
        raise HTTPException(status_code=422, detail=err)
    
    result["image_b64"] = "data:image/jpeg;base64," + base64.b64encode(data).decode()
    
    return result


@app.post("/capture/start")
async def start_capture():
    global etat_camera
    with verrou:
        if etat_camera["statut"] == "en_cours": 
            raise HTTPException(status_code=400, detail="Active")
        etat_camera = {"statut": "en_cours", "donnees": None, "erreur": None}
    threading.Thread(target=thread_camera, daemon=True).start()
    return {"statut": "demarree"}


@app.get("/capture/result")
async def get_capture_result():
    with verrou: 
        return etat_camera


@app.get("/")
async def root():
    return {
        "status": "WhatAPlant 3.0 API Online", 
        "groq_active": True,
        "wikipedia_plat_active": True,
        "no_file_storage": True
    }


# Thread Caméra (modifié - sans amélioration d'image)
def thread_camera():
    global etat_camera
    cap = cv2.VideoCapture(0)
    if not cap.isOpened():
        with verrou:
            etat_camera = {"statut": "erreur", "donnees": None, "erreur": "Caméra introuvable"}
        return

    cap.set(cv2.CAP_PROP_FRAME_WIDTH, 1280)
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 720)
    cv2.namedWindow("WhatAPlant", cv2.WINDOW_NORMAL)
    cv2.setWindowProperty("WhatAPlant", cv2.WND_PROP_FULLSCREEN, cv2.WINDOW_FULLSCREEN)

    debut = time.time()
    duree = 3
    image_capt = None

    while True:
        ret, frame = cap.read()
        if not ret: break

        h, w = frame.shape[:2]
        ecoule = time.time() - debut
        restant = max(0, duree - int(ecoule))

        lw, lh = int(w * .55), int(h * .7)
        x0, y0 = (w - lw) // 2, (h - lh) // 2
        cv2.rectangle(frame, (x0, y0), (x0+lw, y0+lh), (74, 186, 122), 3)

        if restant > 0:
            tw = cv2.getTextSize(str(restant), cv2.FONT_HERSHEY_SIMPLEX, 4, 6)[0][0]
            cv2.putText(frame, str(restant), ((w-tw)//2, h//2), cv2.FONT_HERSHEY_SIMPLEX, 4, (255,255,255), 6)
        else:
            cv2.putText(frame, "Capture!", (w//2-100, h//2), cv2.FONT_HERSHEY_SIMPLEX, 2, (74,186,122), 4)

        overlay = frame.copy()
        cv2.rectangle(overlay, (0, h-50), (w, h), (0,0,0), -1)
        cv2.addWeighted(overlay, .5, frame, .5, 0, frame)
        cv2.putText(frame, f"Capture dans {restant}s | ECHAP = Annuler", (20, h-15), 
                    cv2.FONT_HERSHEY_SIMPLEX, 0.65, (255,255,255), 2)

        cv2.imshow("WhatAPlant", frame)
        if cv2.waitKey(1) & 0xFF == 27: break
        if ecoule >= duree:
            image_capt = frame.copy()
            break

    cap.release()
    cv2.destroyAllWindows()

    if image_capt is None:
        with verrou:
            etat_camera = {"statut": "annule", "donnees": None, "erreur": "Capture annulée"}
        return

    # 🔧 MODIFICATION : Utiliser l'image originale sans amélioration
    img = image_capt
    _, buf = cv2.imencode('.jpg', img, [cv2.IMWRITE_JPEG_QUALITY, 90])
    data = buf.tobytes()
    
    result, err = identifier_plante(data, "camera_capture.jpg")
    if err:
        with verrou:
            etat_camera = {"statut": "erreur", "donnees": None, "erreur": err}
        return

    result["image_b64"] = "data:image/jpeg;base64," + base64.b64encode(data).decode()
    
    with verrou:
        etat_camera = {"statut": "termine", "donnees": result, "erreur": None}


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=5001)