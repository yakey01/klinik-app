import { useState, useEffect } from 'react';

export function AuthDebug() {
  const [authStatus, setAuthStatus] = useState<string>("checking...");
  const [userData, setUserData] = useState<any>(null);

  useEffect(() => {
    // Check Laravel session
    const isAuthenticated = document.querySelector('meta[name="user-authenticated"]')?.getAttribute('content') === 'true';
    const rawUserData = document.querySelector('meta[name="user-data"]')?.getAttribute('content');
    
    setAuthStatus(isAuthenticated ? "authenticated" : "not authenticated");
    
    if (rawUserData) {
      try {
        setUserData(JSON.parse(rawUserData));
      } catch (e) {
        setUserData({ error: "Failed to parse user data" });
      }
    }
  }, []);

  return (
    <div style={{ 
      position: "fixed", 
      top: 0, 
      right: 0, 
      background: "rgba(0,0,0,0.8)", 
      color: "white", 
      padding: "10px", 
      fontSize: "12px",
      zIndex: 9999,
      maxWidth: "300px"
    }}>
      <h4>🔍 Auth Debug</h4>
      <p>Status: {authStatus}</p>
      <p>User: {userData ? JSON.stringify(userData, null, 2) : "none"}</p>
    </div>
  );
}
