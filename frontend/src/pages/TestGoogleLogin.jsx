import { GoogleOAuthProvider, GoogleLogin } from '@react-oauth/google';

export default function TestGoogleLogin() {
  return (
    <GoogleOAuthProvider clientId="124375599165-shqllg5gs01gc2kfppkm38ftcdti5n1t.apps.googleusercontent.com">
      <div style={{ textAlign: "center", marginTop: "60px" }}>
        <h1>測試 Google 登入</h1>

        <div style={{ display: 'flex', justifyContent: 'center', marginTop: '40px' }}>
          <GoogleLogin
            onSuccess={(res) => {
              console.log('✅ 登入成功', res);
              alert('登入成功');
            }}
            onError={() => {
              console.error('❌ 登入失敗');
              alert('登入失敗');
            }}
          />
        </div>
      </div>
    </GoogleOAuthProvider>
  );
}
