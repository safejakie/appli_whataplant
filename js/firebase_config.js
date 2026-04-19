// js/firebase-config.js - Configuration Firebase Auth

// 1. On utilise uniquement les imports via CDN (URL HTTPS) pour éviter les doublons
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js";
import { 
    getAuth, 
    sendPasswordResetEmail, 
    confirmPasswordReset, 
    verifyPasswordResetCode,
    onAuthStateChanged 
} from "https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js";

// 2. Ta configuration Firebase
const firebaseConfig = {
  apiKey: "AIzaSyCOs0At3Mk2BD7zqw-g0nzc2yk_FG5G4g0",
  authDomain: "wathaplant.firebaseapp.com",
  projectId: "wathaplant",
  storageBucket: "wathaplant.firebasestorage.app",
  messagingSenderId: "279896679692",
  appId: "1:279896679692:web:d9179a5bc9f820ddb4d529"
};

// 3. Initialisation de Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);

// 4. Exportation des fonctions pour les utiliser dans tes autres fichiers (login.js, etc.)
export { 
    auth, 
    sendPasswordResetEmail, 
    confirmPasswordReset, 
    verifyPasswordResetCode,
    onAuthStateChanged 
};