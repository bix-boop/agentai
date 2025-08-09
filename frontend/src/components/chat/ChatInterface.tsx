import React, { useState, useRef, useEffect } from 'react';
import { PaperAirplaneIcon, PhotoIcon, Cog6ToothIcon } from '@heroicons/react/24/outline';
import MessageBubble from './MessageBubble';
import ChatSettings from './ChatSettings';
import VoiceControls from './VoiceControls';
import { useChat } from '../../hooks/useChat';
import { useAuth } from '../../hooks/useAuth';
import { Message, Chat, AIAssistant } from '../../types/chat';

interface ChatInterfaceProps {
  chat: Chat | null;
  aiAssistant: AIAssistant;
  onNewChat?: () => void;
}

const ChatInterface: React.FC<ChatInterfaceProps> = ({
  chat,
  aiAssistant,
  onNewChat
}) => {
  const [message, setMessage] = useState('');
  const [showSettings, setShowSettings] = useState(false);
  const [isGeneratingImage, setIsGeneratingImage] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const textareaRef = useRef<HTMLTextAreaElement>(null);

  const { user } = useAuth();
  const {
    messages,
    isLoading,
    sendMessage,
    generateImage,
    updateChatSettings
  } = useChat(chat?.id);

  // Auto-scroll to bottom when new messages arrive
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  // Auto-resize textarea
  useEffect(() => {
    if (textareaRef.current) {
      textareaRef.current.style.height = 'auto';
      textareaRef.current.style.height = `${textareaRef.current.scrollHeight}px`;
    }
  }, [message]);

  const handleSendMessage = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!message.trim() || isLoading) return;

    const messageText = message.trim();
    setMessage('');

    // Handle image generation command
    if (messageText.startsWith('/img ')) {
      const prompt = messageText.substring(5);
      if (prompt && aiAssistant.enable_image_generation) {
        setIsGeneratingImage(true);
        try {
          await generateImage(prompt);
        } finally {
          setIsGeneratingImage(false);
        }
        return;
      }
    }

    await sendMessage(messageText);
  };

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSendMessage(e);
    }
  };

  const handleImageUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      // Handle image upload logic
      console.log('Image upload:', file);
    }
  };

  const isMessageValid = message.trim().length >= (aiAssistant.min_message_length || 1) &&
                        message.trim().length <= (aiAssistant.max_message_length || 5000);

  return (
    <div className="flex flex-col h-full bg-white dark:bg-gray-900">
      {/* Chat Header */}
      <div className="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <div className="flex items-center space-x-3">
          <img
            src={aiAssistant.avatar_url}
            alt={aiAssistant.name}
            className="w-10 h-10 rounded-full"
          />
          <div>
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
              {aiAssistant.name}
            </h2>
            <p className="text-sm text-gray-500 dark:text-gray-400">
              {aiAssistant.expertise || 'AI Assistant'}
            </p>
          </div>
        </div>

        <div className="flex items-center space-x-2">
          {/* Credits Display */}
          <div className="flex items-center space-x-1 px-3 py-1 bg-blue-100 dark:bg-blue-900 rounded-full">
            <span className="text-sm font-medium text-blue-800 dark:text-blue-200">
              {user?.credits_balance || 0} credits
            </span>
          </div>

          {/* Voice Controls */}
          {aiAssistant.enable_voice && (
            <VoiceControls />
          )}

          {/* Settings Button */}
          <button
            onClick={() => setShowSettings(!showSettings)}
            className="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            <Cog6ToothIcon className="w-5 h-5" />
          </button>
        </div>
      </div>

      {/* Chat Settings Panel */}
      {showSettings && (
        <ChatSettings
          chat={chat}
          aiAssistant={aiAssistant}
          onUpdateSettings={updateChatSettings}
          onClose={() => setShowSettings(false)}
        />
      )}

      {/* Messages Area */}
      <div className="flex-1 overflow-y-auto p-4 space-y-4">
        {/* Welcome Message */}
        {(!messages || messages.length === 0) && (
          <div className="text-center py-8">
            <img
              src={aiAssistant.avatar_url}
              alt={aiAssistant.name}
              className="w-16 h-16 rounded-full mx-auto mb-4"
            />
            <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
              Welcome to {aiAssistant.name}
            </h3>
            <p className="text-gray-600 dark:text-gray-400 max-w-md mx-auto">
              {aiAssistant.welcome_message || `I'm ${aiAssistant.name}, ready to help you with ${aiAssistant.expertise || 'various tasks'}.`}
            </p>
            
            {/* Feature highlights */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8 max-w-2xl mx-auto">
              {aiAssistant.supported_languages && (
                <div className="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <h4 className="font-semibold text-gray-900 dark:text-white mb-2">
                    Multi-language
                  </h4>
                  <p className="text-sm text-gray-600 dark:text-gray-400">
                    I can respond in {aiAssistant.supported_languages.length} languages
                  </p>
                </div>
              )}
              
              {aiAssistant.enable_image_generation && (
                <div className="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <h4 className="font-semibold text-gray-900 dark:text-white mb-2">
                    Image Generation
                  </h4>
                  <p className="text-sm text-gray-600 dark:text-gray-400">
                    Type "/img [description]" to generate images
                  </p>
                </div>
              )}
              
              {aiAssistant.response_tones && (
                <div className="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <h4 className="font-semibold text-gray-900 dark:text-white mb-2">
                    Adaptive Tone
                  </h4>
                  <p className="text-sm text-gray-600 dark:text-gray-400">
                    I can adjust my tone and style to your preference
                  </p>
                </div>
              )}
            </div>
          </div>
        )}

        {/* Message List */}
        {messages?.map((msg) => (
          <MessageBubble
            key={msg.id}
            message={msg}
            aiAssistant={aiAssistant}
            showAvatar={true}
          />
        ))}

        {/* Loading Indicator */}
        {(isLoading || isGeneratingImage) && (
          <div className="flex items-start space-x-3">
            <img
              src={aiAssistant.avatar_url}
              alt={aiAssistant.name}
              className="w-8 h-8 rounded-full"
            />
            <div className="bg-gray-100 dark:bg-gray-800 rounded-2xl px-4 py-3">
              <div className="flex space-x-1">
                <div className="w-2 h-2 bg-gray-500 rounded-full animate-bounce"></div>
                <div className="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style={{ animationDelay: '0.1s' }}></div>
                <div className="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style={{ animationDelay: '0.2s' }}></div>
              </div>
              <p className="text-xs text-gray-500 mt-1">
                {isGeneratingImage ? 'Generating image...' : 'Thinking...'}
              </p>
            </div>
          </div>
        )}

        <div ref={messagesEndRef} />
      </div>

      {/* Input Area */}
      <div className="border-t border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
        <form onSubmit={handleSendMessage} className="flex items-end space-x-3">
          {/* Image Upload */}
          <div className="flex-shrink-0">
            <label className="cursor-pointer">
              <input
                type="file"
                accept="image/*"
                onChange={handleImageUpload}
                className="hidden"
              />
              <div className="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                <PhotoIcon className="w-5 h-5" />
              </div>
            </label>
          </div>

          {/* Message Input */}
          <div className="flex-1 relative">
            <textarea
              ref={textareaRef}
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              onKeyPress={handleKeyPress}
              placeholder={`Message ${aiAssistant.name}...`}
              className="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border-0 rounded-2xl resize-none focus:ring-2 focus:ring-blue-500 focus:bg-white dark:focus:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
              rows={1}
              maxLength={aiAssistant.max_message_length || 5000}
              disabled={isLoading || isGeneratingImage}
            />
            
            {/* Character count */}
            <div className="absolute bottom-1 right-3 text-xs text-gray-400">
              {message.length}/{aiAssistant.max_message_length || 5000}
            </div>
          </div>

          {/* Send Button */}
          <button
            type="submit"
            disabled={!isMessageValid || isLoading || isGeneratingImage}
            className="flex-shrink-0 p-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white rounded-2xl transition-colors"
          >
            <PaperAirplaneIcon className="w-5 h-5" />
          </button>
        </form>

        {/* Input Hints */}
        <div className="mt-2 flex flex-wrap gap-2 text-xs text-gray-500">
          {aiAssistant.enable_image_generation && (
            <span className="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">
              Type "/img" to generate images
            </span>
          )}
          <span className="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">
            Press Shift+Enter for new line
          </span>
          {message.length < (aiAssistant.min_message_length || 1) && (
            <span className="px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded">
              Minimum {aiAssistant.min_message_length} characters
            </span>
          )}
        </div>
      </div>
    </div>
  );
};

export default ChatInterface;