import React, { useState, useEffect } from 'react';
import { useAuth } from '../../hooks/useAuth';
import { ApiService } from '../../services/api';
import { 
  SparklesIcon, 
  HeartIcon,
  StarIcon,
  ChatBubbleLeftRightIcon,
  FunnelIcon,
  MagnifyingGlassIcon
} from '@heroicons/react/24/outline';
import { HeartIcon as HeartSolidIcon } from '@heroicons/react/24/solid';

interface AIAssistant {
  id: number;
  name: string;
  slug: string;
  description: string;
  expertise: string;
  avatar_url: string;
  category: {
    id: number;
    name: string;
    color: string;
  };
  average_rating: number;
  total_ratings: number;
  usage_count: number;
  is_favorited: boolean;
  required_packages: number[];
  minimum_tier: number;
}

interface Category {
  id: number;
  name: string;
  slug: string;
  color: string;
  icon: string;
}

const AIAssistantGallery: React.FC = () => {
  const { user } = useAuth();
  const [assistants, setAssistants] = useState<AIAssistant[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedCategory, setSelectedCategory] = useState<number | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [sortBy, setSortBy] = useState<'popular' | 'rating' | 'newest'>('popular');

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      const [assistantsResponse, categoriesResponse] = await Promise.all([
        ApiService.get('/ai-assistants'),
        ApiService.get('/categories'),
      ]);

      if (assistantsResponse.success) {
        setAssistants(assistantsResponse.data);
      }

      if (categoriesResponse.success) {
        setCategories(categoriesResponse.data);
      }
    } catch (error) {
      console.error('Failed to load data:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleFavorite = async (assistantId: number) => {
    try {
      const response = await ApiService.post(`/ai-assistants/${assistantId}/favorite`);
      
      if (response.success) {
        setAssistants(prev => prev.map(assistant => 
          assistant.id === assistantId 
            ? { ...assistant, is_favorited: !assistant.is_favorited }
            : assistant
        ));
      }
    } catch (error) {
      console.error('Failed to toggle favorite:', error);
    }
  };

  const handleStartChat = async (assistant: AIAssistant) => {
    try {
      const response = await ApiService.post('/chats', {
        ai_assistant_id: assistant.id,
      });

      if (response.success) {
        // Navigate to chat interface
        window.location.href = `/chat/${response.data.id}`;
      }
    } catch (error) {
      console.error('Failed to start chat:', error);
      alert('Failed to start chat. Please try again.');
    }
  };

  const filteredAssistants = assistants
    .filter(assistant => {
      if (selectedCategory && assistant.category?.id !== selectedCategory) return false;
      if (searchTerm && !assistant.name.toLowerCase().includes(searchTerm.toLowerCase()) &&
          !assistant.description.toLowerCase().includes(searchTerm.toLowerCase()) &&
          !assistant.expertise?.toLowerCase().includes(searchTerm.toLowerCase())) return false;
      return true;
    })
    .sort((a, b) => {
      switch (sortBy) {
        case 'rating':
          return b.average_rating - a.average_rating;
        case 'newest':
          return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
        case 'popular':
        default:
          return b.usage_count - a.usage_count;
      }
    });

  const canAccessAssistant = (assistant: AIAssistant): boolean => {
    if (!user) return false;
    if (assistant.minimum_tier > user.current_tier) return false;
    if (assistant.required_packages?.length > 0) {
      // Check if user has purchased any of the required packages
      // This would need to be implemented in the backend
      return true; // For now, assume access
    }
    return true;
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        <span className="ml-3 text-gray-600">Loading AI assistants...</span>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {/* Header */}
      <div className="text-center mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-4">
          <SparklesIcon className="h-8 w-8 inline mr-2 text-primary-600" />
          AI Assistant Gallery
        </h1>
        <p className="text-gray-600 max-w-2xl mx-auto">
          Discover specialized AI assistants for every need. From business consulting to creative writing, 
          find the perfect AI companion for your tasks.
        </p>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <div className="flex flex-col lg:flex-row gap-4">
          {/* Search */}
          <div className="flex-1">
            <div className="relative">
              <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
              <input
                type="text"
                placeholder="Search AI assistants..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
          </div>

          {/* Category Filter */}
          <div className="flex-shrink-0">
            <select
              value={selectedCategory || ''}
              onChange={(e) => setSelectedCategory(e.target.value ? parseInt(e.target.value) : null)}
              className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
              <option value="">All Categories</option>
              {categories.map(category => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
          </div>

          {/* Sort */}
          <div className="flex-shrink-0">
            <select
              value={sortBy}
              onChange={(e) => setSortBy(e.target.value as 'popular' | 'rating' | 'newest')}
              className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
              <option value="popular">Most Popular</option>
              <option value="rating">Highest Rated</option>
              <option value="newest">Newest</option>
            </select>
          </div>
        </div>

        {/* Category Chips */}
        <div className="flex flex-wrap gap-2 mt-4">
          <button
            onClick={() => setSelectedCategory(null)}
            className={`px-3 py-1 rounded-full text-sm font-medium ${
              selectedCategory === null
                ? 'bg-primary-100 text-primary-800 border border-primary-200'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            All
          </button>
          {categories.map(category => (
            <button
              key={category.id}
              onClick={() => setSelectedCategory(category.id)}
              className={`px-3 py-1 rounded-full text-sm font-medium ${
                selectedCategory === category.id
                  ? 'text-white border'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
              style={{
                backgroundColor: selectedCategory === category.id ? category.color : undefined,
                borderColor: selectedCategory === category.id ? category.color : undefined,
              }}
            >
              {category.name}
            </button>
          ))}
        </div>
      </div>

      {/* Results Count */}
      <div className="mb-6">
        <p className="text-gray-600">
          {filteredAssistants.length} AI assistant{filteredAssistants.length !== 1 ? 's' : ''} found
        </p>
      </div>

      {/* AI Assistants Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {filteredAssistants.map(assistant => (
          <div key={assistant.id} className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
            {/* Header */}
            <div className="p-6 pb-4">
              <div className="flex items-start justify-between mb-4">
                <div className="flex items-center">
                  <img
                    src={assistant.avatar_url}
                    alt={assistant.name}
                    className="w-12 h-12 rounded-full object-cover"
                  />
                  <div className="ml-3">
                    <h3 className="text-lg font-semibold text-gray-900">{assistant.name}</h3>
                    {assistant.expertise && (
                      <p className="text-sm text-gray-500">{assistant.expertise}</p>
                    )}
                  </div>
                </div>
                
                <button
                  onClick={() => handleFavorite(assistant.id)}
                  className="p-2 hover:bg-gray-50 rounded-full"
                >
                  {assistant.is_favorited ? (
                    <HeartSolidIcon className="h-5 w-5 text-red-500" />
                  ) : (
                    <HeartIcon className="h-5 w-5 text-gray-400" />
                  )}
                </button>
              </div>

              {/* Category Badge */}
              {assistant.category && (
                <div className="mb-3">
                  <span
                    className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white"
                    style={{ backgroundColor: assistant.category.color }}
                  >
                    {assistant.category.name}
                  </span>
                </div>
              )}

              {/* Description */}
              <p className="text-gray-600 text-sm mb-4 line-clamp-3">
                {assistant.description}
              </p>

              {/* Stats */}
              <div className="flex items-center justify-between text-sm text-gray-500 mb-4">
                <div className="flex items-center">
                  <StarIcon className="h-4 w-4 text-yellow-400 mr-1" />
                  <span>{assistant.average_rating.toFixed(1)}</span>
                  <span className="ml-1">({assistant.total_ratings})</span>
                </div>
                <div className="flex items-center">
                  <ChatBubbleLeftRightIcon className="h-4 w-4 mr-1" />
                  <span>{assistant.usage_count.toLocaleString()} chats</span>
                </div>
              </div>

              {/* Access Level */}
              {assistant.minimum_tier > 1 && (
                <div className="mb-4">
                  <span className="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-800">
                    Tier {assistant.minimum_tier}+ Required
                  </span>
                </div>
              )}
            </div>

            {/* Actions */}
            <div className="px-6 pb-6">
              {canAccessAssistant(assistant) ? (
                <button
                  onClick={() => handleStartChat(assistant)}
                  className="w-full bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium flex items-center justify-center"
                >
                  <ChatBubbleLeftRightIcon className="h-4 w-4 mr-2" />
                  Start Chat
                </button>
              ) : (
                <div className="text-center">
                  <p className="text-sm text-gray-500 mb-2">
                    {assistant.minimum_tier > user?.current_tier ? 
                      `Requires Tier ${assistant.minimum_tier}` : 
                      'Premium Access Required'
                    }
                  </p>
                  <button
                    onClick={() => window.location.href = '/pricing'}
                    className="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium"
                  >
                    Upgrade Access
                  </button>
                </div>
              )}
            </div>
          </div>
        ))}
      </div>

      {/* Empty State */}
      {filteredAssistants.length === 0 && (
        <div className="text-center py-12">
          <SparklesIcon className="h-16 w-16 text-gray-300 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">No AI assistants found</h3>
          <p className="text-gray-500 mb-6">
            {searchTerm || selectedCategory 
              ? 'Try adjusting your search or filter criteria.'
              : 'No AI assistants are available yet.'
            }
          </p>
          {(searchTerm || selectedCategory) && (
            <button
              onClick={() => {
                setSearchTerm('');
                setSelectedCategory(null);
              }}
              className="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium"
            >
              Clear Filters
            </button>
          )}
        </div>
      )}

      {/* Load More (if pagination needed) */}
      {filteredAssistants.length > 0 && filteredAssistants.length % 12 === 0 && (
        <div className="text-center mt-8">
          <button className="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-medium">
            Load More
          </button>
        </div>
      )}
    </div>
  );
};

export default AIAssistantGallery;