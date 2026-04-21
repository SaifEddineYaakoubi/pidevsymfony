// Utilitaires pour convertir entre Base64 et ArrayBuffer
function base64urlToBuffer(base64url) {
    const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
    const padLen = (4 - (base64.length % 4)) % 4;
    const padded = base64 + '='.repeat(padLen);
    const binary = atob(padded);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes.buffer;
}

function bufferToBase64url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    const base64 = btoa(binary);
    return base64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}

// Enregistrement d'un nouveau Face ID
async function registerWebAuthn() {
    try {
        // 1. Obtenir les options du serveur
        const optionsResponse = await fetch('/webauthn/register/options', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        });

        if (!optionsResponse.ok) {
            const error = await optionsResponse.json();
            throw new Error(error.error || 'Erreur lors de la récupération des options');
        }

        const options = await optionsResponse.json();

        // 2. Convertir les données en format attendu par l'API WebAuthn
        const publicKeyCredentialCreationOptions = {
            challenge: base64urlToBuffer(options.challenge),
            rp: options.rp,
            user: {
                id: base64urlToBuffer(options.user.id),
                name: options.user.name,
                displayName: options.user.displayName
            },
            pubKeyCredParams: options.pubKeyCredParams,
            timeout: options.timeout,
            excludeCredentials: options.excludeCredentials?.map(cred => ({
                type: cred.type,
                id: base64urlToBuffer(cred.id),
                transports: cred.transports
            })) || [],
            authenticatorSelection: options.authenticatorSelection,
            attestation: options.attestation
        };

        // 3. Appeler l'API WebAuthn du navigateur (Face ID / Touch ID / Windows Hello)
        const credential = await navigator.credentials.create({
            publicKey: publicKeyCredentialCreationOptions
        });

        if (!credential) {
            throw new Error('Aucune credential créée');
        }

        // 4. Préparer les données pour l'envoi au serveur
        const credentialData = {
            id: credential.id,
            rawId: bufferToBase64url(credential.rawId),
            type: credential.type,
            response: {
                clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
                attestationObject: bufferToBase64url(credential.response.attestationObject)
            }
        };

        // 5. Envoyer au serveur pour vérification
        const verifyResponse = await fetch('/webauthn/register/verify', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(credentialData)
        });

        if (!verifyResponse.ok) {
            const error = await verifyResponse.json();
            throw new Error(error.error || 'Erreur lors de la vérification');
        }

        const result = await verifyResponse.json();
        return result;

    } catch (error) {
        console.error('Erreur WebAuthn:', error);
        throw error;
    }
}

// Authentification avec Face ID
async function loginWebAuthn(email) {
    try {
        // 1. Obtenir les options du serveur
        const optionsResponse = await fetch('/webauthn/login/options', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email })
        });

        if (!optionsResponse.ok) {
            const error = await optionsResponse.json();
            throw new Error(error.error || 'Erreur lors de la récupération des options');
        }

        const options = await optionsResponse.json();

        // 2. Convertir les données
        const publicKeyCredentialRequestOptions = {
            challenge: base64urlToBuffer(options.challenge),
            timeout: options.timeout,
            rpId: options.rpId,
            allowCredentials: options.allowCredentials?.map(cred => ({
                type: cred.type,
                id: base64urlToBuffer(cred.id),
                transports: cred.transports
            })) || [],
            userVerification: options.userVerification
        };

        // 3. Appeler l'API WebAuthn (Face ID / Touch ID / Windows Hello)
        const assertion = await navigator.credentials.get({
            publicKey: publicKeyCredentialRequestOptions
        });

        if (!assertion) {
            throw new Error('Authentification annulée');
        }

        // 4. Préparer les données pour l'envoi
        const assertionData = {
            id: assertion.id,
            rawId: bufferToBase64url(assertion.rawId),
            type: assertion.type,
            response: {
                clientDataJSON: bufferToBase64url(assertion.response.clientDataJSON),
                authenticatorData: bufferToBase64url(assertion.response.authenticatorData),
                signature: bufferToBase64url(assertion.response.signature),
                userHandle: assertion.response.userHandle ? bufferToBase64url(assertion.response.userHandle) : null
            }
        };

        // 5. Envoyer au serveur pour vérification
        const verifyResponse = await fetch('/webauthn/login/verify', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(assertionData)
        });

        if (!verifyResponse.ok) {
            const error = await verifyResponse.json();
            throw new Error(error.error || 'Erreur lors de la vérification');
        }

        const result = await verifyResponse.json();
        return result;

    } catch (error) {
        console.error('Erreur WebAuthn Login:', error);
        throw error;
    }
}

// Vérifier si WebAuthn est disponible
function isWebAuthnAvailable() {
    return window.PublicKeyCredential !== undefined && 
           navigator.credentials !== undefined;
}

// Vérifier si l'authentification par plateforme est disponible (Face ID, Touch ID, Windows Hello)
async function isPlatformAuthenticatorAvailable() {
    if (!isWebAuthnAvailable()) {
        return false;
    }
    
    try {
        return await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
    } catch (error) {
        console.error('Erreur lors de la vérification du platform authenticator:', error);
        return false;
    }
}
