import axios, { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios';
import { ApiResponse, ApiError } from '../types/chat';

// API Configuration
const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';
const REQUEST_TIMEOUT = 30000; // 30 seconds

// Create axios instance
const api: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  timeout: REQUEST_TIMEOUT,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Token management
class TokenManager {
  private static TOKEN_KEY = 'phoenix_ai_token';
  private static USER_KEY = 'phoenix_ai_user';

  static getToken(): string | null {
    return localStorage.getItem(this.TOKEN_KEY);
  }

  static setToken(token: string): void {
    localStorage.setItem(this.TOKEN_KEY, token);
  }

  static removeToken(): void {
    localStorage.removeItem(this.TOKEN_KEY);
    localStorage.removeItem(this.USER_KEY);
  }

  static getUser(): any | null {
    const user = localStorage.getItem(this.USER_KEY);
    return user ? JSON.parse(user) : null;
  }

  static setUser(user: any): void {
    localStorage.setItem(this.USER_KEY, JSON.stringify(user));
  }
}

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const token = TokenManager.getToken();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor to handle errors and token refresh
api.interceptors.response.use(
  (response: AxiosResponse) => {
    return response;
  },
  (error) => {
    if (error.response?.status === 401) {
      // Token expired or invalid
      TokenManager.removeToken();
      window.location.href = '/login';
    }
    
    return Promise.reject(error);
  }
);

// Generic API functions
export class ApiService {
  // Generic GET request
  static async get<T>(
    endpoint: string, 
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response = await api.get(endpoint, config);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  // Generic POST request
  static async post<T>(
    endpoint: string, 
    data?: any, 
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response = await api.post(endpoint, data, config);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  // Generic PUT request
  static async put<T>(
    endpoint: string, 
    data?: any, 
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response = await api.put(endpoint, data, config);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  // Generic PATCH request
  static async patch<T>(
    endpoint: string, 
    data?: any, 
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response = await api.patch(endpoint, data, config);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  // Generic DELETE request
  static async delete<T>(
    endpoint: string, 
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response = await api.delete(endpoint, config);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  // File upload
  static async uploadFile<T>(
    endpoint: string,
    file: File,
    fieldName: string = 'file',
    additionalData?: Record<string, any>
  ): Promise<ApiResponse<T>> {
    try {
      const formData = new FormData();
      formData.append(fieldName, file);
      
      if (additionalData) {
        Object.keys(additionalData).forEach(key => {
          formData.append(key, additionalData[key]);
        });
      }

      const response = await api.post(endpoint, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  // Download file
  static async downloadFile(
    endpoint: string,
    filename?: string
  ): Promise<void> {
    try {
      const response = await api.get(endpoint, {
        responseType: 'blob',
      });

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', filename || 'download');
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (error) {
      throw this.handleError(error);
    }
  }

  // Error handling
  private static handleError(error: any): ApiError {
    if (error.response) {
      // Server responded with error status
      const { status, data } = error.response;
      
      return {
        message: data?.message || data?.error || 'An error occurred',
        code: data?.error_code || 'server_error',
        status,
        errors: data?.errors ? this.formatValidationErrors(data.errors) : undefined,
      };
    } else if (error.request) {
      // Network error
      return {
        message: 'Network error. Please check your connection.',
        code: 'network_error',
        status: 0,
      };
    } else {
      // Other error
      return {
        message: error.message || 'An unexpected error occurred',
        code: 'unknown_error',
      };
    }
  }

  // Format Laravel validation errors
  private static formatValidationErrors(errors: Record<string, string[]>) {
    return Object.keys(errors).map(field => ({
      field,
      message: errors[field][0], // Take first error message
    }));
  }

  // Authentication helpers
  static setAuthToken(token: string): void {
    TokenManager.setToken(token);
  }

  static setUser(user: any): void {
    TokenManager.setUser(user);
  }

  static getUser(): any | null {
    return TokenManager.getUser();
  }

  static clearAuth(): void {
    TokenManager.removeToken();
  }

  static isAuthenticated(): boolean {
    return !!TokenManager.getToken();
  }
}

// Specific API endpoints
export const endpoints = {
  // Authentication
  auth: {
    login: '/auth/login',
    register: '/auth/register',
    logout: '/auth/logout',
    me: '/auth/me',
    refresh: '/auth/refresh',
    forgotPassword: '/auth/forgot-password',
    resetPassword: '/auth/reset-password',
    verifyEmail: '/auth/verify-email',
  },

  // Users
  users: {
    profile: '/user/profile',
    updateProfile: '/user/profile',
    changePassword: '/user/change-password',
    credits: '/user/credits',
    transactions: '/user/transactions',
    chats: '/user/chats',
  },

  // AI Assistants
  aiAssistants: {
    list: '/ai-assistants',
    create: '/ai-assistants',
    show: (id: number) => `/ai-assistants/${id}`,
    update: (id: number) => `/ai-assistants/${id}`,
    delete: (id: number) => `/ai-assistants/${id}`,
    popular: '/ai-assistants/popular',
    recent: '/ai-assistants/recent',
    search: '/ai-assistants/search',
  },

  // Categories
  categories: {
    list: '/categories',
    show: (slug: string) => `/categories/${slug}`,
    aiAssistants: (slug: string) => `/categories/${slug}/ai-assistants`,
  },

  // Chats
  chats: {
    list: '/chats',
    create: '/chats',
    show: (id: number) => `/chats/${id}`,
    sendMessage: (id: number) => `/chats/${id}/messages`,
    generateImage: (id: number) => `/chats/${id}/generate-image`,
    archive: (id: number) => `/chats/${id}/archive`,
    restore: (id: number) => `/chats/${id}/restore`,
    delete: (id: number) => `/chats/${id}`,
    updateSettings: (id: number) => `/chats/${id}/settings`,
  },

  // Credit Packages
  creditPackages: {
    list: '/credit-packages',
    show: (id: number) => `/credit-packages/${id}`,
    purchase: (id: number) => `/credit-packages/${id}/purchase`,
  },

  // Payments
  payments: {
    createStripeIntent: '/payments/stripe/create-intent',
    confirmStripePayment: '/payments/stripe/confirm',
    createPayPalOrder: '/payments/paypal/create-order',
    capturePayPalOrder: '/payments/paypal/capture-order',
    bankDeposit: '/payments/bank-deposit',
    webhook: '/payments/webhook',
  },

  // Admin
  admin: {
    dashboard: '/admin/dashboard',
    users: '/admin/users',
    aiAssistants: '/admin/ai-assistants',
    transactions: '/admin/transactions',
    analytics: '/admin/analytics',
    settings: '/admin/settings',
  },

  // System
  system: {
    settings: '/system/settings',
    health: '/system/health',
    version: '/system/version',
  },
};

// Export the configured axios instance for direct use if needed
export { api };
export { TokenManager };
export default ApiService;