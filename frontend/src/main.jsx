// import { StrictMode } from 'react'
// import { createRoot } from 'react-dom/client'
// import App from './App.jsx'
// import { GoogleOAuthProvider } from '@react-oauth/google';

// createRoot(document.getElementById('root')).render(
//   //<StrictMode>
//   <GoogleOAuthProvider clientId="124375599165-shqllg5gs01gc2kfppkm38ftcdti5n1t.apps.googleusercontent.com">
//     <App />
//   </GoogleOAuthProvider>
//   //</StrictMode>,
// )
import { createRoot } from 'react-dom/client';
import TestGoogleLogin from './pages/TestGoogleLogin';

createRoot(document.getElementById('root')).render(
  <TestGoogleLogin />
);