import React from 'react';
import { ChevronRightIcon, SparklesIcon, ChatBubbleLeftRightIcon, CreditCardIcon } from '@heroicons/react/24/outline';

interface LandingPageProps {
  onLogin: () => void;
  onRegister: () => void;
}

const LandingPage: React.FC<LandingPageProps> = ({ onLogin, onRegister }) => {
  return (
    <div className="min-h-screen bg-gradient-to-br from-primary-50 to-secondary-50">
      {/* Navigation */}
      <nav className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center">
              <SparklesIcon className="h-8 w-8 text-primary-600" />
              <span className="ml-2 text-xl font-bold text-gray-900">Phoenix AI</span>
            </div>
            <div className="flex space-x-4">
              <button
                onClick={onLogin}
                className="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium"
              >
                Sign In
              </button>
              <button
                onClick={onRegister}
                className="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm font-medium"
              >
                Get Started
              </button>
            </div>
          </div>
        </div>
      </nav>

      {/* Hero Section */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div className="text-center">
          <h1 className="text-4xl md:text-6xl font-bold text-gray-900 mb-6">
            Unleash the Power of
            <span className="text-primary-600 block">Artificial Intelligence</span>
          </h1>
          <p className="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
            Phoenix AI brings you intelligent assistants specialized in business, creativity, education, and more. 
            Experience the future of AI-powered productivity.
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <button
              onClick={onRegister}
              className="bg-primary-600 hover:bg-primary-700 text-white px-8 py-3 rounded-lg text-lg font-semibold flex items-center justify-center"
            >
              Start Free Trial
              <ChevronRightIcon className="ml-2 h-5 w-5" />
            </button>
            <button
              onClick={onLogin}
              className="border border-gray-300 hover:border-gray-400 text-gray-700 px-8 py-3 rounded-lg text-lg font-semibold"
            >
              Sign In
            </button>
          </div>
        </div>
      </div>

      {/* Features Section */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div className="grid md:grid-cols-3 gap-8">
          <div className="text-center p-6">
            <ChatBubbleLeftRightIcon className="h-12 w-12 text-primary-600 mx-auto mb-4" />
            <h3 className="text-xl font-semibold text-gray-900 mb-2">Intelligent Conversations</h3>
            <p className="text-gray-600">
              Chat with specialized AI assistants trained for specific tasks and industries.
            </p>
          </div>
          <div className="text-center p-6">
            <SparklesIcon className="h-12 w-12 text-primary-600 mx-auto mb-4" />
            <h3 className="text-xl font-semibold text-gray-900 mb-2">Advanced AI Models</h3>
            <p className="text-gray-600">
              Powered by the latest OpenAI models including GPT-4 for superior intelligence.
            </p>
          </div>
          <div className="text-center p-6">
            <CreditCardIcon className="h-12 w-12 text-primary-600 mx-auto mb-4" />
            <h3 className="text-xl font-semibold text-gray-900 mb-2">Flexible Pricing</h3>
            <p className="text-gray-600">
              Pay-as-you-go credit system with packages tailored to your usage needs.
            </p>
          </div>
        </div>
      </div>

      {/* Footer */}
      <footer className="bg-white border-t border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div className="text-center text-gray-500">
            <p>&copy; 2025 Phoenix AI. All rights reserved.</p>
          </div>
        </div>
      </footer>
    </div>
  );
};

export default LandingPage;