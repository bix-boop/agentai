// User types
export interface User {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  role: 'user' | 'admin' | 'moderator';
  credits_balance: number;
  total_credits_purchased: number;
  total_credits_used: number;
  current_tier: number;
  tier_expires_at?: string;
  is_active: boolean;
  is_verified: boolean;
  created_at: string;
  updated_at: string;
}

// Category types
export interface Category {
  id: number;
  name: string;
  slug: string;
  description?: string;
  icon?: string;
  color: string;
  is_active: boolean;
  show_on_homepage: boolean;
  sort_order: number;
  ai_assistants_count?: number;
  created_at: string;
  updated_at: string;
}

// AI Assistant types
export interface AIAssistant {
  id: number;
  user_id: number;
  category_id?: number;
  name: string;
  slug: string;
  description: string;
  expertise?: string;
  welcome_message: string;
  avatar?: string;
  avatar_url: string;
  
  // AI Configuration
  system_prompt: string;
  temperature: number;
  frequency_penalty: number;
  presence_penalty: number;
  max_tokens: number;
  model: string;
  
  // Message Configuration
  min_message_length: number;
  max_message_length: number;
  conversation_memory: number;
  
  // Features
  enable_voice: boolean;
  enable_image_generation: boolean;
  enable_web_search: boolean;
  supported_languages?: string[];
  response_tones?: string[];
  writing_styles?: string[];
  
  // Access Control
  is_public: boolean;
  required_packages?: number[];
  minimum_tier: number;
  
  // Statistics
  is_active: boolean;
  usage_count: number;
  average_rating: number;
  total_ratings: number;
  
  // Content Safety
  content_filter_enabled: boolean;
  blocked_words?: string[];
  
  // Relationships
  user?: User;
  category?: Category;
  
  created_at: string;
  updated_at: string;
}

// Chat types
export interface Chat {
  id: number;
  user_id: number;
  ai_assistant_id: number;
  title?: string;
  settings?: ChatSettings;
  message_count: number;
  credits_used: number;
  last_activity_at?: string;
  is_archived: boolean;
  
  // Relationships
  user?: User;
  ai_assistant?: AIAssistant;
  messages?: Message[];
  
  created_at: string;
  updated_at: string;
}

// Chat Settings
export interface ChatSettings {
  language?: string;
  tone?: string;
  writing_style?: string;
  temperature?: number;
  max_tokens?: number;
  [key: string]: any;
}

// Message types
export interface Message {
  id: number;
  chat_id: number;
  role: 'user' | 'assistant' | 'system';
  content: string;
  metadata?: MessageMetadata;
  credits_consumed: number;
  tokens_used?: number;
  model_used?: string;
  processing_time?: number;
  is_edited: boolean;
  is_flagged: boolean;
  flag_reason?: string;
  created_at: string;
  updated_at: string;
}

// Message Metadata
export interface MessageMetadata {
  type?: 'text' | 'image_generation' | 'voice' | 'file';
  prompt?: string;
  images?: GeneratedImage[];
  openai_response?: any;
  settings_used?: ChatSettings;
  [key: string]: any;
}

// Generated Image
export interface GeneratedImage {
  url: string;
  revised_prompt?: string;
}

// Credit Package types
export interface CreditPackage {
  id: number;
  name: string;
  description: string;
  image?: string;
  credits: number;
  price_cents: number;
  currency: string;
  tier: number;
  features?: string[];
  ai_access?: number[];
  is_popular: boolean;
  is_active: boolean;
  sort_order: number;
  purchase_count: number;
  discount_percentage: number;
  sale_ends_at?: string;
  created_at: string;
  updated_at: string;
}

// Transaction types
export interface Transaction {
  id: number;
  user_id: number;
  credit_package_id?: number;
  transaction_id: string;
  type: 'purchase' | 'refund' | 'bonus' | 'admin_adjustment';
  status: 'pending' | 'completed' | 'failed' | 'cancelled' | 'refunded';
  payment_method: 'stripe' | 'paypal' | 'bank_deposit' | 'admin' | 'bonus';
  
  // Amounts
  credits_amount: number;
  price_cents: number;
  currency: string;
  discount_applied: number;
  
  // Payment Gateway Data
  gateway_transaction_id?: string;
  gateway_response?: any;
  payment_intent_id?: string;
  invoice_url?: string;
  
  // Bank Deposit Specific
  bank_reference?: string;
  bank_verified_at?: string;
  verified_by?: number;
  
  // Metadata
  metadata?: any;
  notes?: string;
  ip_address?: string;
  user_agent?: string;
  
  processed_at?: string;
  created_at: string;
  updated_at: string;
}

// API Response types
export interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  message?: string;
  errors?: Record<string, string[]>;
  error?: string;
  error_code?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

// Chat API specific responses
export interface ChatResponse extends ApiResponse {
  chat?: Chat;
  credits_used?: number;
  processing_time?: number;
}

export interface MessageResponse extends ApiResponse {
  user_message?: Message;
  ai_message?: Message;
  credits_used?: number;
  processing_time?: number;
  user_credits_remaining?: number;
}

export interface ImageGenerationResponse extends ApiResponse {
  message?: Message;
  images?: GeneratedImage[];
  credits_used?: number;
  user_credits_remaining?: number;
}

// Hook types
export interface UseChatReturn {
  messages: Message[];
  isLoading: boolean;
  error: string | null;
  sendMessage: (content: string, settings?: ChatSettings) => Promise<void>;
  generateImage: (prompt: string, size?: string) => Promise<void>;
  updateChatSettings: (settings: ChatSettings) => Promise<void>;
  clearError: () => void;
}

export interface UseAuthReturn {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (data: RegisterData) => Promise<void>;
  logout: () => Promise<void>;
  updateProfile: (data: Partial<User>) => Promise<void>;
}

// Form types
export interface LoginForm {
  email: string;
  password: string;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface AIAssistantForm {
  name: string;
  category_id?: number;
  description: string;
  expertise?: string;
  welcome_message: string;
  avatar?: File;
  system_prompt: string;
  temperature: number;
  frequency_penalty: number;
  presence_penalty: number;
  max_tokens: number;
  model: string;
  min_message_length: number;
  max_message_length: number;
  conversation_memory: number;
  enable_voice: boolean;
  enable_image_generation: boolean;
  enable_web_search: boolean;
  supported_languages?: string[];
  response_tones?: string[];
  writing_styles?: string[];
  is_public: boolean;
  required_packages?: number[];
  minimum_tier: number;
  content_filter_enabled: boolean;
  blocked_words?: string[];
}

// Settings types
export interface SystemSettings {
  site_name: string;
  site_description: string;
  site_logo?: string;
  default_language: string;
  default_currency: string;
  credit_per_character: number;
  welcome_credits: number;
  max_free_messages: number;
  openai_api_key: string;
  stripe_publishable_key?: string;
  paypal_client_id?: string;
  google_analytics_id?: string;
  allow_registration: boolean;
  require_email_verification: boolean;
  enable_dark_mode: boolean;
  maintenance_mode: boolean;
  [key: string]: any;
}

// Error types
export interface ValidationError {
  field: string;
  message: string;
}

export interface ApiError {
  message: string;
  code?: string;
  status?: number;
  errors?: ValidationError[];
}