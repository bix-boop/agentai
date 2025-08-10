import React, { useState, useEffect } from 'react';
import { ApiService } from '../../services/api';
import {
  ChartBarIcon,
  UsersIcon,
  CreditCardIcon,
  ChatBubbleLeftRightIcon,
  SparklesIcon,
  TrendingUpIcon,
  BanknotesIcon,
  EyeIcon
} from '@heroicons/react/24/outline';

interface DashboardStats {
  overview: {
    total_users: number;
    active_users: number;
    new_users_today: number;
    total_chats: number;
    active_chats: number;
    total_messages: number;
    messages_today: number;
    total_ai_assistants: number;
    public_ai_assistants: number;
  };
  revenue: {
    total_revenue: number;
    revenue_this_month: number;
    revenue_today: number;
    pending_revenue: number;
  };
  credits: {
    total_credits_purchased: number;
    total_credits_used: number;
    credits_purchased_today: number;
    credits_consumed_today: number;
  };
  engagement: {
    avg_messages_per_chat: number;
    avg_credits_per_chat: number;
    most_active_users: any[];
    most_popular_ais: any[];
  };
}

const AnalyticsDashboard: React.FC = () => {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [timeRange, setTimeRange] = useState(30);

  useEffect(() => {
    loadStats();
  }, [timeRange]);

  const loadStats = async () => {
    try {
      setLoading(true);
      const response = await ApiService.get(`/admin/analytics/dashboard?days=${timeRange}`);
      
      if (response.success) {
        setStats(response.data);
      }
    } catch (error) {
      console.error('Failed to load analytics:', error);
    } finally {
      setLoading(false);
    }
  };

  const formatCurrency = (cents: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
    }).format(cents / 100);
  };

  const formatNumber = (num: number) => {
    return new Intl.NumberFormat('en-US').format(num);
  };

  if (loading || !stats) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        <span className="ml-3 text-gray-600">Loading analytics...</span>
      </div>
    );
  }

  const overviewCards = [
    {
      title: 'Total Users',
      value: formatNumber(stats.overview.total_users),
      subtitle: `${stats.overview.new_users_today} new today`,
      icon: UsersIcon,
      color: 'bg-blue-500',
      change: '+12%',
    },
    {
      title: 'Active Users',
      value: formatNumber(stats.overview.active_users),
      subtitle: `${timeRange} days`,
      icon: EyeIcon,
      color: 'bg-green-500',
      change: '+8%',
    },
    {
      title: 'Total Revenue',
      value: formatCurrency(stats.revenue.total_revenue),
      subtitle: `${formatCurrency(stats.revenue.revenue_today)} today`,
      icon: BanknotesIcon,
      color: 'bg-purple-500',
      change: '+23%',
    },
    {
      title: 'Total Messages',
      value: formatNumber(stats.overview.total_messages),
      subtitle: `${formatNumber(stats.overview.messages_today)} today`,
      icon: ChatBubbleLeftRightIcon,
      color: 'bg-orange-500',
      change: '+15%',
    },
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Analytics Dashboard</h1>
          <p className="text-gray-600">Comprehensive platform performance metrics</p>
        </div>
        
        <div className="flex items-center space-x-4">
          <select
            value={timeRange}
            onChange={(e) => setTimeRange(parseInt(e.target.value))}
            className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"
          >
            <option value={7}>Last 7 days</option>
            <option value={30}>Last 30 days</option>
            <option value={90}>Last 90 days</option>
            <option value={365}>Last year</option>
          </select>
          
          <button
            onClick={loadStats}
            className="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium"
          >
            Refresh
          </button>
        </div>
      </div>

      {/* Overview Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {overviewCards.map((card, index) => (
          <div key={index} className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{card.title}</p>
                <p className="text-2xl font-bold text-gray-900">{card.value}</p>
                <p className="text-sm text-gray-500">{card.subtitle}</p>
              </div>
              <div className={`p-3 rounded-lg ${card.color}`}>
                <card.icon className="h-6 w-6 text-white" />
              </div>
            </div>
            {card.change && (
              <div className="mt-4 flex items-center">
                <TrendingUpIcon className="h-4 w-4 text-green-500 mr-1" />
                <span className="text-sm font-medium text-green-600">{card.change}</span>
                <span className="text-sm text-gray-500 ml-1">vs last period</span>
              </div>
            )}
          </div>
        ))}
      </div>

      {/* Revenue & Credits Section */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Revenue Breakdown */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Revenue Breakdown</h3>
          <div className="space-y-4">
            <div className="flex justify-between items-center">
              <span className="text-gray-600">This Month</span>
              <span className="font-semibold text-gray-900">
                {formatCurrency(stats.revenue.revenue_this_month)}
              </span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-gray-600">Today</span>
              <span className="font-semibold text-gray-900">
                {formatCurrency(stats.revenue.revenue_today)}
              </span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-gray-600">Pending Approval</span>
              <span className="font-semibold text-orange-600">
                {formatCurrency(stats.revenue.pending_revenue)}
              </span>
            </div>
            <div className="pt-4 border-t border-gray-200">
              <div className="flex justify-between items-center">
                <span className="text-gray-900 font-medium">Total Revenue</span>
                <span className="text-xl font-bold text-green-600">
                  {formatCurrency(stats.revenue.total_revenue)}
                </span>
              </div>
            </div>
          </div>
        </div>

        {/* Credits Overview */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Credits Overview</h3>
          <div className="space-y-4">
            <div className="flex justify-between items-center">
              <span className="text-gray-600">Total Purchased</span>
              <span className="font-semibold text-gray-900">
                {formatNumber(stats.credits.total_credits_purchased)}
              </span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-gray-600">Total Consumed</span>
              <span className="font-semibold text-gray-900">
                {formatNumber(stats.credits.total_credits_used)}
              </span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-gray-600">Purchased Today</span>
              <span className="font-semibold text-green-600">
                {formatNumber(stats.credits.credits_purchased_today)}
              </span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-gray-600">Consumed Today</span>
              <span className="font-semibold text-blue-600">
                {formatNumber(stats.credits.credits_consumed_today)}
              </span>
            </div>
            <div className="pt-4 border-t border-gray-200">
              <div className="flex justify-between items-center">
                <span className="text-gray-900 font-medium">Utilization Rate</span>
                <span className="text-lg font-bold text-primary-600">
                  {stats.credits.total_credits_purchased > 0 
                    ? Math.round((stats.credits.total_credits_used / stats.credits.total_credits_purchased) * 100)
                    : 0
                  }%
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Engagement Metrics */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Chat Engagement */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Chat Engagement</h3>
          <div className="space-y-4">
            <div className="flex justify-between items-center">
              <span className="text-gray-600">Total Chats</span>
              <span className="font-semibold text-gray-900">
                {formatNumber(stats.overview.total_chats)}
              </span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-gray-600">Active Chats</span>
              <span className="font-semibold text-green-600">
                {formatNumber(stats.overview.active_chats)}
              </span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-gray-600">Avg Messages/Chat</span>
              <span className="font-semibold text-gray-900">
                {stats.engagement.avg_messages_per_chat?.toFixed(1) || '0'}
              </span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-gray-600">Avg Credits/Chat</span>
              <span className="font-semibold text-gray-900">
                {formatNumber(Math.round(stats.engagement.avg_credits_per_chat || 0))}
              </span>
            </div>
          </div>
        </div>

        {/* AI Assistant Stats */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">AI Assistant Stats</h3>
          <div className="space-y-4">
            <div className="flex justify-between items-center">
              <span className="text-gray-600">Total AI Assistants</span>
              <span className="font-semibold text-gray-900">
                {formatNumber(stats.overview.total_ai_assistants)}
              </span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-gray-600">Public Assistants</span>
              <span className="font-semibold text-green-600">
                {formatNumber(stats.overview.public_ai_assistants)}
              </span>
            </div>
            {stats.engagement.most_popular_ais?.length > 0 && (
              <div>
                <p className="text-gray-600 mb-2">Most Popular:</p>
                <div className="space-y-1">
                  {stats.engagement.most_popular_ais.slice(0, 3).map((ai, index) => (
                    <div key={ai.id} className="flex justify-between items-center text-sm">
                      <span className="text-gray-700">{ai.name}</span>
                      <span className="text-gray-500">{formatNumber(ai.usage_count)} uses</span>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Top Performers */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Most Active Users */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Most Active Users</h3>
          <div className="space-y-3">
            {stats.engagement.most_active_users?.map((user, index) => (
              <div key={user.id} className="flex items-center justify-between">
                <div className="flex items-center">
                  <div className="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center mr-3">
                    <span className="text-primary-600 font-semibold text-sm">{index + 1}</span>
                  </div>
                  <div>
                    <p className="font-medium text-gray-900">{user.name}</p>
                    <p className="text-sm text-gray-500">{user.email}</p>
                  </div>
                </div>
                <div className="text-right">
                  <p className="font-semibold text-gray-900">
                    {formatNumber(user.total_credits_used)}
                  </p>
                  <p className="text-sm text-gray-500">credits used</p>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Most Popular AI Assistants */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Most Popular AI Assistants</h3>
          <div className="space-y-3">
            {stats.engagement.most_popular_ais?.map((ai, index) => (
              <div key={ai.id} className="flex items-center justify-between">
                <div className="flex items-center">
                  <div className="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                    <span className="text-green-600 font-semibold text-sm">{index + 1}</span>
                  </div>
                  <div>
                    <p className="font-medium text-gray-900">{ai.name}</p>
                    <p className="text-sm text-gray-500">
                      ⭐ {ai.average_rating?.toFixed(1) || '0.0'} ({ai.total_ratings || 0} ratings)
                    </p>
                  </div>
                </div>
                <div className="text-right">
                  <p className="font-semibold text-gray-900">
                    {formatNumber(ai.usage_count)}
                  </p>
                  <p className="text-sm text-gray-500">chats</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <button className="flex items-center justify-center px-4 py-3 bg-primary-50 hover:bg-primary-100 text-primary-700 rounded-lg border border-primary-200">
            <ChartBarIcon className="h-5 w-5 mr-2" />
            Generate Report
          </button>
          <button className="flex items-center justify-center px-4 py-3 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg border border-green-200">
            <UsersIcon className="h-5 w-5 mr-2" />
            User Management
          </button>
          <button className="flex items-center justify-center px-4 py-3 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg border border-purple-200">
            <SparklesIcon className="h-5 w-5 mr-2" />
            AI Assistants
          </button>
        </div>
      </div>

      {/* Real-time Metrics */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Real-time Activity</h3>
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="text-center p-4 bg-blue-50 rounded-lg">
            <p className="text-2xl font-bold text-blue-600">
              {formatNumber(stats.overview.active_users)}
            </p>
            <p className="text-sm text-blue-600">Users Online</p>
          </div>
          <div className="text-center p-4 bg-green-50 rounded-lg">
            <p className="text-2xl font-bold text-green-600">
              {formatNumber(stats.overview.active_chats)}
            </p>
            <p className="text-sm text-green-600">Active Chats</p>
          </div>
          <div className="text-center p-4 bg-purple-50 rounded-lg">
            <p className="text-2xl font-bold text-purple-600">
              {formatNumber(stats.credits.credits_consumed_today)}
            </p>
            <p className="text-sm text-purple-600">Credits Today</p>
          </div>
          <div className="text-center p-4 bg-orange-50 rounded-lg">
            <p className="text-2xl font-bold text-orange-600">
              {formatCurrency(stats.revenue.revenue_today)}
            </p>
            <p className="text-sm text-orange-600">Revenue Today</p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AnalyticsDashboard;