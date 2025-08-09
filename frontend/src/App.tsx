import React, { useEffect, useState } from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from './hooks/useAuth';
import AuthModal from './components/auth/AuthModal';
import Dashboard from './pages/Dashboard';
import AdminDashboard from './pages/AdminDashboard';
import LandingPage from './pages/LandingPage';
import LoadingSpinner from './components/common/LoadingSpinner';

function App() {
  const { user, isLoading, isAuthenticated } = useAuth();
  const [showAuthModal, setShowAuthModal] = useState(false);
  const [authMode, setAuthMode] = useState<'login' | 'register'>('login');

  // Show loading spinner while checking authentication
  if (isLoading) {
    return <LoadingSpinner />;
  }

  return (
    <Router>
      <div className="min-h-screen bg-gray-50">
        <Routes>
          {/* Public Routes */}
          <Route 
            path="/" 
            element={
              isAuthenticated ? (
                user?.role === 'admin' ? (
                  <Navigate to="/admin" replace />
                ) : (
                  <Navigate to="/dashboard" replace />
                )
              ) : (
                <LandingPage 
                  onLogin={() => {
                    setAuthMode('login');
                    setShowAuthModal(true);
                  }}
                  onRegister={() => {
                    setAuthMode('register');
                    setShowAuthModal(true);
                  }}
                />
              )
            } 
          />
          
          {/* Protected Routes */}
          <Route 
            path="/dashboard" 
            element={
              isAuthenticated ? (
                <Dashboard />
              ) : (
                <Navigate to="/" replace />
              )
            } 
          />
          
          {/* Admin Routes */}
          <Route 
            path="/admin/*" 
            element={
              isAuthenticated && user?.role === 'admin' ? (
                <AdminDashboard />
              ) : (
                <Navigate to="/" replace />
              )
            } 
          />
          
          {/* Fallback */}
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>

        {/* Auth Modal */}
        {showAuthModal && (
          <AuthModal
            mode={authMode}
            onClose={() => setShowAuthModal(false)}
            onSuccess={() => {
              setShowAuthModal(false);
              // Navigation will be handled by the auth state change
            }}
          />
        )}
      </div>
    </Router>
  );
}

export default App;
