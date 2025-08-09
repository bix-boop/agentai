import React, { useState } from 'react';
import { ClipboardDocumentIcon, HandThumbUpIcon, HandThumbDownIcon, SpeakerWaveIcon } from '@heroicons/react/24/outline';
import { ClipboardDocumentIcon as ClipboardDocumentSolidIcon, HandThumbUpIcon as HandThumbUpSolidIcon, HandThumbDownIcon as HandThumbDownSolidIcon } from '@heroicons/react/24/solid';
import ReactMarkdown from 'react-markdown';
import { Message, AIAssistant } from '../../types/chat';

interface MessageBubbleProps {
  message: Message;
  aiAssistant: AIAssistant;
  showAvatar?: boolean;
}

const MessageBubble: React.FC<MessageBubbleProps> = ({
  message,
  aiAssistant,
  showAvatar = true
}) => {
  const [copied, setCopied] = useState(false);
  const [rating, setRating] = useState<'like' | 'dislike' | null>(null);
  const [isPlaying, setIsPlaying] = useState(false);

  const isUser = message.role === 'user';
  const isAssistant = message.role === 'assistant';
  const isSystem = message.role === 'system';

  // Handle copy to clipboard
  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(message.content);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (error) {
      console.error('Failed to copy message:', error);
    }
  };

  // Handle message rating
  const handleRating = (newRating: 'like' | 'dislike') => {
    setRating(rating === newRating ? null : newRating);
    // TODO: Send rating to backend
  };

  // Handle text-to-speech
  const handleSpeak = () => {
    if (isPlaying) {
      speechSynthesis.cancel();
      setIsPlaying(false);
      return;
    }

    const utterance = new SpeechSynthesisUtterance(message.content);
    utterance.onstart = () => setIsPlaying(true);
    utterance.onend = () => setIsPlaying(false);
    utterance.onerror = () => setIsPlaying(false);
    
    speechSynthesis.speak(utterance);
  };

  // Don't render system messages
  if (isSystem) return null;

  // Handle image generation messages
  const isImageGeneration = message.metadata?.type === 'image_generation';
  const images = message.metadata?.images || [];

  return (
    <div className={`flex ${isUser ? 'justify-end' : 'justify-start'} mb-4`}>
      <div className={`flex ${isUser ? 'flex-row-reverse' : 'flex-row'} items-start space-x-3 max-w-4xl`}>
        {/* Avatar */}
        {showAvatar && !isUser && (
          <img
            src={aiAssistant.avatar_url}
            alt={aiAssistant.name}
            className="w-8 h-8 rounded-full flex-shrink-0"
          />
        )}

        {/* Message Content */}
        <div className={`group relative ${isUser ? 'ml-3' : 'mr-3'}`}>
          {/* Message Bubble */}
          <div
            className={`px-4 py-3 rounded-2xl ${
              isUser
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white'
            } ${isUser ? 'rounded-br-md' : 'rounded-bl-md'}`}
          >
            {/* Image Generation Display */}
            {isImageGeneration && images.length > 0 && (
              <div className="mb-3">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  {images.map((image: any, index: number) => (
                    <div key={index} className="relative">
                      <img
                        src={image.url}
                        alt={message.metadata?.prompt || 'Generated image'}
                        className="rounded-lg w-full h-auto cursor-pointer hover:opacity-90 transition-opacity"
                        onClick={() => window.open(image.url, '_blank')}
                      />
                    </div>
                  ))}
                </div>
                {message.metadata?.prompt && (
                  <p className="text-sm opacity-75 mt-2">
                    <strong>Prompt:</strong> {message.metadata.prompt}
                  </p>
                )}
              </div>
            )}

            {/* Text Content */}
            <div className="prose prose-sm max-w-none">
              {isUser ? (
                <p className="whitespace-pre-wrap">{message.content}</p>
              ) : (
                <ReactMarkdown
                  components={{
                    // Custom rendering for code blocks
                    code: ({ node, inline, className, children, ...props }) => {
                      return inline ? (
                        <code className="bg-gray-200 dark:bg-gray-700 px-1 rounded text-sm" {...props}>
                          {children}
                        </code>
                      ) : (
                        <pre className="bg-gray-200 dark:bg-gray-700 p-3 rounded-lg overflow-x-auto">
                          <code className={className} {...props}>
                            {children}
                          </code>
                        </pre>
                      );
                    },
                    // Custom rendering for links
                    a: ({ href, children }) => (
                      <a
                        href={href}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-blue-600 hover:text-blue-800 underline"
                      >
                        {children}
                      </a>
                    ),
                  }}
                >
                  {message.content}
                </ReactMarkdown>
              )}
            </div>
          </div>

          {/* Message Actions */}
          {!isUser && (
            <div className="flex items-center space-x-2 mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
              {/* Copy Button */}
              <button
                onClick={handleCopy}
                className="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded"
                title={copied ? 'Copied!' : 'Copy message'}
              >
                {copied ? (
                  <ClipboardDocumentSolidIcon className="w-4 h-4 text-green-600" />
                ) : (
                  <ClipboardDocumentIcon className="w-4 h-4" />
                )}
              </button>

              {/* Text-to-Speech Button */}
              {aiAssistant.enable_voice && (
                <button
                  onClick={handleSpeak}
                  className={`p-1 rounded ${
                    isPlaying
                      ? 'text-blue-600 dark:text-blue-400'
                      : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                  }`}
                  title={isPlaying ? 'Stop speaking' : 'Read aloud'}
                >
                  <SpeakerWaveIcon className="w-4 h-4" />
                </button>
              )}

              {/* Rating Buttons */}
              <div className="flex items-center space-x-1">
                <button
                  onClick={() => handleRating('like')}
                  className={`p-1 rounded ${
                    rating === 'like'
                      ? 'text-green-600 dark:text-green-400'
                      : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                  }`}
                  title="Good response"
                >
                  {rating === 'like' ? (
                    <HandThumbUpSolidIcon className="w-4 h-4" />
                  ) : (
                    <HandThumbUpIcon className="w-4 h-4" />
                  )}
                </button>

                <button
                  onClick={() => handleRating('dislike')}
                  className={`p-1 rounded ${
                    rating === 'dislike'
                      ? 'text-red-600 dark:text-red-400'
                      : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                  }`}
                  title="Poor response"
                >
                  {rating === 'dislike' ? (
                    <HandThumbDownSolidIcon className="w-4 h-4" />
                  ) : (
                    <HandThumbDownIcon className="w-4 h-4" />
                  )}
                </button>
              </div>
            </div>
          )}

          {/* Message Metadata */}
          <div className="flex items-center justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
            <div className="flex items-center space-x-2">
              <span>{new Date(message.created_at).toLocaleTimeString()}</span>
              {message.credits_consumed > 0 && (
                <span className="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded">
                  {message.credits_consumed} credits
                </span>
              )}
              {message.processing_time && (
                <span>
                  {(message.processing_time * 1000).toFixed(0)}ms
                </span>
              )}
            </div>

            {message.tokens_used && (
              <span>{message.tokens_used} tokens</span>
            )}
          </div>

          {/* Message Status Indicators */}
          {message.is_flagged && (
            <div className="mt-2 p-2 bg-red-100 dark:bg-red-900 border border-red-300 dark:border-red-700 rounded text-sm text-red-800 dark:text-red-200">
              <strong>Content Warning:</strong> This message has been flagged for review.
              {message.flag_reason && (
                <div className="mt-1 text-xs">Reason: {message.flag_reason}</div>
              )}
            </div>
          )}

          {message.is_edited && (
            <div className="mt-1 text-xs text-gray-400 italic">
              (edited)
            </div>
          )}
        </div>

        {/* User Avatar */}
        {showAvatar && isUser && (
          <div className="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">
            U
          </div>
        )}
      </div>
    </div>
  );
};

export default MessageBubble;