import React, { useState, useEffect } from 'react';
import { useAuth } from '../hooks/useAuth';
import { 
  ChatBubbleLeftRightIcon, 
  SparklesIcon, 
  CreditCardIcon,
  UserCircleIcon,
  Cog6ToothIcon,
  ArrowRightOnRectangleIcon
} from '@heroicons/react/24/outline';

const Dashboard: React.FC = () => {
  const { user, logout } = useAuth();
  const [activeTab, setActiveTab] = useState('chats');

  const handleLogout = async () => {
    try {
      await logout();
    } catch (error) {
      console.error('Logout failed:', error);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center">
              <SparklesIcon className="h-8 w-8 text-primary-600" />
              <span className="ml-2 text-xl font-bold text-gray-900">Phoenix AI</span>
            </div>
            
            <div className="flex items-center space-x-4">
              <div className="flex items-center space-x-2">
                <CreditCardIcon className="h-5 w-5 text-gray-400" />
                <span className="text-sm font-medium text-gray-700">
                  {user?.credits_balance?.toLocaleString() || 0} credits
                </span>
              </div>
              
              <div className="flex items-center space-x-2">
                <UserCircleIcon className="h-6 w-6 text-gray-400" />
                <span className="text-sm font-medium text-gray-700">{user?.name}</span>
              </div>
              
              <button
                onClick={handleLogout}
                className="text-gray-400 hover:text-gray-600 p-2"
                title="Logout"
              >
                <ArrowRightOnRectangleIcon className="h-5 w-5" />
              </button>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Welcome Section */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900">
            Welcome back, {user?.name}!
          </h1>
          <p className="text-gray-600 mt-2">
            Ready to chat with your AI assistants? You have {user?.credits_balance?.toLocaleString() || 0} credits available.
          </p>
        </div>

        {/* Navigation Tabs */}
        <div className="border-b border-gray-200 mb-8">
          <nav className="-mb-px flex space-x-8">
            <button
              onClick={() => setActiveTab('chats')}
              className={`py-2 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'chats'
                  ? 'border-primary-500 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              <ChatBubbleLeftRightIcon className="h-5 w-5 inline mr-2" />
              My Chats
            </button>
            <button
              onClick={() => setActiveTab('assistants')}
              className={`py-2 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'assistants'
                  ? 'border-primary-500 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              <SparklesIcon className="h-5 w-5 inline mr-2" />
              AI Assistants
            </button>
            <button
              onClick={() => setActiveTab('profile')}
              className={`py-2 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'profile'
                  ? 'border-primary-500 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              <Cog6ToothIcon className="h-5 w-5 inline mr-2" />
              Settings
            </button>
          </nav>
        </div>

        {/* Tab Content */}
        <div className="bg-white rounded-lg shadow p-6">
          {activeTab === 'chats' && (
            <div className="text-center py-12">
              <ChatBubbleLeftRightIcon className="h-16 w-16 text-gray-300 mx-auto mb-4" />
              <h3 className="text-lg font-medium text-gray-900 mb-2">No chats yet</h3>
              <p className="text-gray-500 mb-6">Start a conversation with an AI assistant to see your chats here.</p>
              <button
                onClick={() => setActiveTab('assistants')}
                className="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium"
              >
                Browse AI Assistants
              </button>
            </div>
          )}

          {activeTab === 'assistants' && (
            <div className="text-center py-12">
              <SparklesIcon className="h-16 w-16 text-gray-300 mx-auto mb-4" />
              <h3 className="text-lg font-medium text-gray-900 mb-2">AI Assistants</h3>
              <p className="text-gray-500 mb-6">Browse and chat with specialized AI assistants.</p>
              <p className="text-sm text-gray-400">AI assistant gallery coming soon...</p>
            </div>
          )}

          {activeTab === 'profile' && (
            <div>
              <h3 className="text-lg font-medium text-gray-900 mb-6">Account Settings</h3>
              <div className="space-y-6">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Name</label>
                  <input
                    type="text"
                    value={user?.name || ''}
                    disabled
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Email</label>
                  <input
                    type="email"
                    value={user?.email || ''}
                    disabled
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Role</label>
                  <input
                    type="text"
                    value={user?.role || ''}
                    disabled
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Credits Balance</label>
                  <input
                    type="text"
                    value={user?.credits_balance?.toLocaleString() || '0'}
                    disabled
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50"
                  />
                </div>
              </div>
            </div>
          )}
        </div>
      </main>
    </div>
  );
};

export default Dashboard;