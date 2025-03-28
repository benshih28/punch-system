import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import App from './App.jsx'
import { GoogleOAuthProvider } from '@react-oauth/google';

createRoot(document.getElementById('root')).render(
  //<StrictMode>
  <GoogleOAuthProvider clientId="124375599165-sq2ghcjullkc2diequ7q3fd2ie40k802.apps.googleusercontent.com">
    <App />
  </GoogleOAuthProvider>
  //</StrictMode>,
)
