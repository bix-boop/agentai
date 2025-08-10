import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import ApiService, { endpoints } from '../services/api';
import type {
  ChatSettings,
  Message,
  UseChatReturn,
  ImageGenerationResponse,
  MessageResponse,
} from '../types/chat';

export const useChat = (chatId?: number | null): UseChatReturn => {
  const [messages, setMessages] = useState<Message[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const latestChatIdRef = useRef<number | null>(chatId ?? null);

  // Track latest chatId for async safety
  useEffect(() => {
    latestChatIdRef.current = chatId ?? null;
  }, [chatId]);

  // Load chat messages when chatId changes
  useEffect(() => {
    const loadChat = async () => {
      if (!chatId) {
        setMessages([]);
        return;
      }
      try {
        setIsLoading(true);
        setError(null);
        const response = await ApiService.get<{ chat: { messages?: Message[] } }>(
          endpoints.chats.show(chatId)
        );
        if (response?.success) {
          const msgs = (response.data as any)?.chat?.messages || (response.data as any)?.messages || [];
          setMessages(Array.isArray(msgs) ? msgs : []);
        } else {
          setError(response?.message || 'Failed to load chat');
        }
      } catch (err: any) {
        setError(err?.message || 'Failed to load chat');
      } finally {
        setIsLoading(false);
      }
    };

    loadChat();
  }, [chatId]);

  const appendMessages = useCallback((newMessages: Message | Message[] | null | undefined) => {
    if (!newMessages) return;
    setMessages(prev => {
      const toAppend = Array.isArray(newMessages) ? newMessages : [newMessages];
      return [...prev, ...toAppend];
    });
  }, []);

  const sendMessage = useCallback<UseChatReturn['sendMessage']>(
    async (content: string, settings?: ChatSettings) => {
      if (!latestChatIdRef.current) {
        setError('No active chat.');
        return;
      }
      try {
        setIsLoading(true);
        setError(null);
        const response = await ApiService.post<MessageResponse>(
          endpoints.chats.sendMessage(latestChatIdRef.current),
          {
            content,
            ...(settings ? { settings } : {}),
          }
        );

        if (response?.success) {
          const { user_message, ai_message } = (response.data as any) || {};
          if (user_message || ai_message) {
            const additions: Message[] = [];
            if (user_message) additions.push(user_message as Message);
            if (ai_message) additions.push(ai_message as Message);
            appendMessages(additions);
          } else {
            // Fallback: refresh chat
            const refresh = await ApiService.get<{ chat: { messages?: Message[] } }>(
              endpoints.chats.show(latestChatIdRef.current)
            );
            if (refresh?.success) {
              const msgs = (refresh.data as any)?.chat?.messages || (refresh.data as any)?.messages || [];
              setMessages(Array.isArray(msgs) ? msgs : []);
            }
          }
        } else {
          setError(response?.message || 'Failed to send message');
        }
      } catch (err: any) {
        setError(err?.message || 'Failed to send message');
      } finally {
        setIsLoading(false);
      }
    },
    [appendMessages]
  );

  const generateImage = useCallback<UseChatReturn['generateImage']>(
    async (prompt: string, size?: string) => {
      if (!latestChatIdRef.current) {
        setError('No active chat.');
        return;
      }
      try {
        setIsLoading(true);
        setError(null);
        const response = await ApiService.post<ImageGenerationResponse>(
          endpoints.chats.generateImage(latestChatIdRef.current),
          {
            prompt,
            ...(size ? { size } : {}),
          }
        );
        if (response?.success) {
          const { message } = (response.data as any) || {};
          if (message) appendMessages(message as Message);
        } else {
          setError(response?.message || 'Failed to generate image');
        }
      } catch (err: any) {
        setError(err?.message || 'Failed to generate image');
      } finally {
        setIsLoading(false);
      }
    },
    [appendMessages]
  );

  const updateChatSettings = useCallback<UseChatReturn['updateChatSettings']>(
    async (settings: ChatSettings) => {
      if (!latestChatIdRef.current) {
        setError('No active chat.');
        return;
      }
      try {
        setIsLoading(true);
        setError(null);
        const response = await ApiService.put(
          endpoints.chats.updateSettings(latestChatIdRef.current),
          settings
        );
        if (!response?.success) {
          setError(response?.message || 'Failed to update chat settings');
        }
      } catch (err: any) {
        setError(err?.message || 'Failed to update chat settings');
      } finally {
        setIsLoading(false);
      }
    },
    []
  );

  const clearError = useCallback(() => setError(null), []);

  return useMemo(
    () => ({
      messages,
      isLoading,
      error,
      sendMessage,
      generateImage,
      updateChatSettings,
      clearError,
    }),
    [messages, isLoading, error, sendMessage, generateImage, updateChatSettings, clearError]
  );
};

export default useChat;