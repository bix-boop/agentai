import { useState, useEffect, useCallback } from 'react';
import { User, LoginForm, RegisterData, UseAuthReturn, ApiError } from '../types/chat';
import ApiService, { endpoints, TokenManager } from '../services/api';

export const useAuth = (): UseAuthReturn => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  // Check if user is authenticated on mount
  useEffect(() => {
    checkAuthStatus();
  }, []);

  /**
   * Check authentication status
   */
  const checkAuthStatus = useCallback(async () => {
    try {
      setIsLoading(true);
      
      const token = TokenManager.getToken();
      if (!token) {
        setUser(null);
        setIsAuthenticated(false);
        return;
      }

      // Try to get user data from API
      const response = await ApiService.get<{ user: User }>(endpoints.auth.me);
      
      if (response.success && response.data?.user) {
        setUser(response.data.user);
        setIsAuthenticated(true);
        TokenManager.setUser(response.data.user);
      } else {
        // Invalid token
        TokenManager.removeToken();
        setUser(null);
        setIsAuthenticated(false);
      }
    } catch (error: any) {
      // Token is invalid or expired
      TokenManager.removeToken();
      setUser(null);
      setIsAuthenticated(false);
    } finally {
      setIsLoading(false);
    }
  }, []);

  /**
   * Login user
   */
  const login = useCallback(async (email: string, password: string, remember: boolean = false) => {
    try {
      setIsLoading(true);

      const response = await ApiService.post<{
        user: User;
        token: string;
      }>(endpoints.auth.login, {
        email,
        password,
        remember,
      });

      if (response.success && response.data) {
        const { user: userData, token } = response.data;
        
        // Store token and user data
        TokenManager.setToken(token);
        TokenManager.setUser(userData);
        
        // Update state
        setUser(userData);
        setIsAuthenticated(true);
      } else {
        throw new Error(response.message || 'Login failed');
      }
    } catch (error: any) {
      setUser(null);
      setIsAuthenticated(false);
      throw error;
    } finally {
      setIsLoading(false);
    }
  }, []);

  /**
   * Register new user
   */
  const register = useCallback(async (data: RegisterData) => {
    try {
      setIsLoading(true);

      const response = await ApiService.post<{
        user: User;
        token: string;
        welcome_credits: number;
      }>(endpoints.auth.register, data);

      if (response.success && response.data) {
        const { user: userData, token } = response.data;
        
        // Store token and user data
        TokenManager.setToken(token);
        TokenManager.setUser(userData);
        
        // Update state
        setUser(userData);
        setIsAuthenticated(true);
      } else {
        throw new Error(response.message || 'Registration failed');
      }
    } catch (error: any) {
      setUser(null);
      setIsAuthenticated(false);
      throw error;
    } finally {
      setIsLoading(false);
    }
  }, []);

  /**
   * Logout user
   */
  const logout = useCallback(async () => {
    try {
      setIsLoading(true);
      
      // Call logout endpoint to invalidate token on server
      await ApiService.post(endpoints.auth.logout);
    } catch (error) {
      // Even if logout fails, clear local data
      console.warn('Logout API call failed:', error);
    } finally {
      // Clear local storage and state
      TokenManager.removeToken();
      setUser(null);
      setIsAuthenticated(false);
      setIsLoading(false);
    }
  }, []);

  /**
   * Update user profile
   */
  const updateProfile = useCallback(async (profileData: Partial<User>) => {
    try {
      setIsLoading(true);

      const response = await ApiService.put<{ user: User }>(
        endpoints.users.updateProfile,
        profileData
      );

      if (response.success && response.data?.user) {
        const updatedUser = response.data.user;
        
        // Update local storage and state
        TokenManager.setUser(updatedUser);
        setUser(updatedUser);
        
        return updatedUser;
      } else {
        throw new Error(response.message || 'Profile update failed');
      }
    } catch (error: any) {
      throw error;
    } finally {
      setIsLoading(false);
    }
  }, []);

  /**
   * Change password
   */
  const changePassword = useCallback(async (currentPassword: string, newPassword: string) => {
    try {
      setIsLoading(true);

      const response = await ApiService.post(endpoints.users.changePassword, {
        current_password: currentPassword,
        password: newPassword,
        password_confirmation: newPassword,
      });

      if (!response.success) {
        throw new Error(response.message || 'Password change failed');
      }
    } catch (error: any) {
      throw error;
    } finally {
      setIsLoading(false);
    }
  }, []);

  /**
   * Send password reset email
   */
  const forgotPassword = useCallback(async (email: string) => {
    try {
      const response = await ApiService.post(endpoints.auth.forgotPassword, { email });
      
      if (!response.success) {
        throw new Error(response.message || 'Failed to send reset email');
      }
    } catch (error: any) {
      throw error;
    }
  }, []);

  /**
   * Reset password with token
   */
  const resetPassword = useCallback(async (
    token: string,
    email: string,
    password: string,
    passwordConfirmation: string
  ) => {
    try {
      const response = await ApiService.post(endpoints.auth.resetPassword, {
        token,
        email,
        password,
        password_confirmation: passwordConfirmation,
      });
      
      if (!response.success) {
        throw new Error(response.message || 'Password reset failed');
      }
    } catch (error: any) {
      throw error;
    }
  }, []);

  /**
   * Resend email verification
   */
  const resendVerification = useCallback(async () => {
    try {
      const response = await ApiService.post(endpoints.auth.resendVerification);
      
      if (!response.success) {
        throw new Error(response.message || 'Failed to resend verification email');
      }
    } catch (error: any) {
      throw error;
    }
  }, []);

  /**
   * Refresh authentication token
   */
  const refreshToken = useCallback(async () => {
    try {
      const response = await ApiService.post<{ token: string }>(endpoints.auth.refresh);
      
      if (response.success && response.data?.token) {
        TokenManager.setToken(response.data.token);
        return response.data.token;
      } else {
        throw new Error('Token refresh failed');
      }
    } catch (error: any) {
      // If refresh fails, logout user
      await logout();
      throw error;
    }
  }, [logout]);

  /**
   * Update user credits (for real-time updates)
   */
  const updateCredits = useCallback((newBalance: number) => {
    if (user) {
      const updatedUser = { ...user, credits_balance: newBalance };
      setUser(updatedUser);
      TokenManager.setUser(updatedUser);
    }
  }, [user]);

  /**
   * Check if user has sufficient credits
   */
  const hasCredits = useCallback((amount: number = 1): boolean => {
    return user ? user.credits_balance >= amount : false;
  }, [user]);

  /**
   * Check if user has specific role
   */
  const hasRole = useCallback((role: string): boolean => {
    return user ? user.role === role : false;
  }, [user]);

  /**
   * Check if user is admin
   */
  const isAdmin = useCallback((): boolean => {
    return hasRole('admin');
  }, [hasRole]);

  /**
   * Check if user is moderator or admin
   */
  const isModerator = useCallback((): boolean => {
    return hasRole('moderator') || hasRole('admin');
  }, [hasRole]);

  return {
    // State
    user,
    isAuthenticated,
    isLoading,
    
    // Core auth methods
    login,
    register,
    logout,
    
    // Profile methods
    updateProfile,
    changePassword,
    
    // Password reset methods
    forgotPassword,
    resetPassword,
    
    // Email verification
    resendVerification,
    
    // Token management
    refreshToken,
    checkAuthStatus,
    
    // Credit management
    updateCredits,
    hasCredits,
    
    // Role checks
    hasRole,
    isAdmin,
    isModerator,
  };
};

export default useAuth;