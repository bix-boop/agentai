import React, { useState, useEffect } from 'react';
import { CheckIcon, StarIcon, CreditCardIcon, BanknotesIcon } from '@heroicons/react/24/outline';
import { StarIcon as StarSolidIcon } from '@heroicons/react/24/solid';
import PaymentForm from './PaymentForm';
import { CreditPackage } from '../../types/chat';
import ApiService, { endpoints } from '../../services/api';

interface CreditPackagesProps {
  onPurchaseComplete?: (transaction: any) => void;
  selectedPackageId?: number;
}

const CreditPackages: React.FC<CreditPackagesProps> = ({
  onPurchaseComplete,
  selectedPackageId
}) => {
  const [packages, setPackages] = useState<CreditPackage[]>([]);
  const [selectedPackage, setSelectedPackage] = useState<CreditPackage | null>(null);
  const [showPaymentForm, setShowPaymentForm] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Load credit packages
  useEffect(() => {
    loadCreditPackages();
  }, []);

  // Auto-select package if provided
  useEffect(() => {
    if (selectedPackageId && packages.length > 0) {
      const pkg = packages.find(p => p.id === selectedPackageId);
      if (pkg) {
        setSelectedPackage(pkg);
        setShowPaymentForm(true);
      }
    }
  }, [selectedPackageId, packages]);

  const loadCreditPackages = async () => {
    try {
      setLoading(true);
      const response = await ApiService.get<CreditPackage[]>(endpoints.creditPackages.list);
      
      if (response.success && response.data) {
        // Sort packages by sort_order and tier
        const sortedPackages = response.data.sort((a, b) => {
          if (a.sort_order !== b.sort_order) {
            return a.sort_order - b.sort_order;
          }
          return a.tier - b.tier;
        });
        setPackages(sortedPackages);
      }
    } catch (err: any) {
      setError(err.message || 'Failed to load credit packages');
    } finally {
      setLoading(false);
    }
  };

  const handleSelectPackage = (pkg: CreditPackage) => {
    setSelectedPackage(pkg);
    setShowPaymentForm(true);
  };

  const handlePaymentComplete = (transaction: any) => {
    setShowPaymentForm(false);
    setSelectedPackage(null);
    onPurchaseComplete?.(transaction);
  };

  const formatPrice = (cents: number, currency: string = 'USD') => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency,
    }).format(cents / 100);
  };

  const formatCredits = (credits: number) => {
    return new Intl.NumberFormat('en-US').format(credits);
  };

  const calculateSavings = (pkg: CreditPackage) => {
    if (pkg.discount_percentage > 0) {
      const originalPrice = pkg.price_cents / (1 - pkg.discount_percentage / 100);
      const savings = originalPrice - pkg.price_cents;
      return Math.round(savings);
    }
    return 0;
  };

  const isOnSale = (pkg: CreditPackage) => {
    return pkg.discount_percentage > 0 && 
           (!pkg.sale_ends_at || new Date(pkg.sale_ends_at) > new Date());
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="text-center py-12">
        <div className="text-red-600 mb-4">{error}</div>
        <button
          onClick={loadCreditPackages}
          className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          Try Again
        </button>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      {/* Header */}
      <div className="text-center mb-12">
        <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">
          Choose Your Credit Package
        </h2>
        <p className="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
          Get credits to chat with AI assistants, generate images, and unlock premium features. 
          Pay only for what you use with our flexible credit system.
        </p>
      </div>

      {/* Credit Packages Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-12">
        {packages.map((pkg) => {
          const savings = calculateSavings(pkg);
          const onSale = isOnSale(pkg);
          
          return (
            <div
              key={pkg.id}
              className={`relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg border-2 transition-all duration-300 hover:shadow-xl ${
                pkg.is_popular 
                  ? 'border-blue-500 ring-2 ring-blue-500 ring-opacity-50' 
                  : 'border-gray-200 dark:border-gray-700 hover:border-blue-300'
              }`}
            >
              {/* Popular Badge */}
              {pkg.is_popular && (
                <div className="absolute -top-3 left-1/2 transform -translate-x-1/2">
                  <div className="bg-blue-600 text-white px-4 py-1 rounded-full text-sm font-semibold flex items-center space-x-1">
                    <StarSolidIcon className="w-4 h-4" />
                    <span>Most Popular</span>
                  </div>
                </div>
              )}

              {/* Sale Badge */}
              {onSale && (
                <div className="absolute -top-2 -right-2">
                  <div className="bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold">
                    {pkg.discount_percentage}% OFF
                  </div>
                </div>
              )}

              <div className="p-6">
                {/* Package Image */}
                {pkg.image && (
                  <div className="flex justify-center mb-4">
                    <img
                      src={pkg.image}
                      alt={pkg.name}
                      className="w-16 h-16 object-cover rounded-lg"
                    />
                  </div>
                )}

                {/* Package Name */}
                <h3 className="text-xl font-bold text-gray-900 dark:text-white text-center mb-2">
                  {pkg.name}
                </h3>

                {/* Credits */}
                <div className="text-center mb-4">
                  <div className="text-3xl font-bold text-blue-600 dark:text-blue-400">
                    {formatCredits(pkg.credits)}
                  </div>
                  <div className="text-sm text-gray-500 dark:text-gray-400">
                    Credits
                  </div>
                </div>

                {/* Pricing */}
                <div className="text-center mb-6">
                  {onSale && savings > 0 && (
                    <div className="text-sm text-gray-500 dark:text-gray-400 line-through">
                      {formatPrice(pkg.price_cents + savings, pkg.currency)}
                    </div>
                  )}
                  <div className="text-2xl font-bold text-gray-900 dark:text-white">
                    {formatPrice(pkg.price_cents, pkg.currency)}
                  </div>
                  {onSale && savings > 0 && (
                    <div className="text-sm text-green-600 dark:text-green-400 font-semibold">
                      Save {formatPrice(savings, pkg.currency)}
                    </div>
                  )}
                </div>

                {/* Description */}
                <p className="text-sm text-gray-600 dark:text-gray-400 text-center mb-6">
                  {pkg.description}
                </p>

                {/* Features */}
                {pkg.features && pkg.features.length > 0 && (
                  <ul className="space-y-2 mb-6">
                    {pkg.features.map((feature, index) => (
                      <li key={index} className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <CheckIcon className="w-4 h-4 text-green-500 mr-2 flex-shrink-0" />
                        <span>{feature}</span>
                      </li>
                    ))}
                  </ul>
                )}

                {/* Tier Badge */}
                {pkg.tier > 1 && (
                  <div className="flex items-center justify-center mb-4">
                    <div className="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-3 py-1 rounded-full text-xs font-semibold flex items-center space-x-1">
                      <StarIcon className="w-3 h-3" />
                      <span>Tier {pkg.tier}</span>
                    </div>
                  </div>
                )}

                {/* Purchase Button */}
                <button
                  onClick={() => handleSelectPackage(pkg)}
                  className={`w-full py-3 px-4 rounded-xl font-semibold transition-colors ${
                    pkg.is_popular
                      ? 'bg-blue-600 hover:bg-blue-700 text-white'
                      : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white'
                  }`}
                >
                  Choose Package
                </button>

                {/* Purchase Count */}
                {pkg.purchase_count > 0 && (
                  <div className="text-xs text-gray-500 dark:text-gray-400 text-center mt-2">
                    {pkg.purchase_count.toLocaleString()} users chose this package
                  </div>
                )}
              </div>
            </div>
          );
        })}
      </div>

      {/* Payment Methods Info */}
      <div className="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 mb-8">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 text-center">
          Secure Payment Methods
        </h3>
        <div className="flex items-center justify-center space-x-8">
          <div className="flex items-center space-x-2">
            <CreditCardIcon className="w-6 h-6 text-gray-600 dark:text-gray-400" />
            <span className="text-sm text-gray-600 dark:text-gray-400">Credit Cards</span>
          </div>
          <div className="flex items-center space-x-2">
            <div className="w-6 h-6 bg-blue-600 rounded flex items-center justify-center text-white text-xs font-bold">
              P
            </div>
            <span className="text-sm text-gray-600 dark:text-gray-400">PayPal</span>
          </div>
          <div className="flex items-center space-x-2">
            <BanknotesIcon className="w-6 h-6 text-gray-600 dark:text-gray-400" />
            <span className="text-sm text-gray-600 dark:text-gray-400">Bank Transfer</span>
          </div>
        </div>
      </div>

      {/* FAQ Section */}
      <div className="bg-white dark:bg-gray-800 rounded-xl p-6">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          Frequently Asked Questions
        </h3>
        <div className="space-y-4">
          <div>
            <h4 className="font-semibold text-gray-900 dark:text-white mb-1">
              How do credits work?
            </h4>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              Credits are consumed based on the length of AI responses. Typically, 1 credit = 1 character in the response.
            </p>
          </div>
          <div>
            <h4 className="font-semibold text-gray-900 dark:text-white mb-1">
              Do credits expire?
            </h4>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              No, credits never expire. Use them at your own pace.
            </p>
          </div>
          <div>
            <h4 className="font-semibold text-gray-900 dark:text-white mb-1">
              Can I get a refund?
            </h4>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              We offer refunds within 30 days for unused credits. Contact support for assistance.
            </p>
          </div>
        </div>
      </div>

      {/* Payment Form Modal */}
      {showPaymentForm && selectedPackage && (
        <PaymentForm
          creditPackage={selectedPackage}
          onSuccess={handlePaymentComplete}
          onCancel={() => {
            setShowPaymentForm(false);
            setSelectedPackage(null);
          }}
        />
      )}
    </div>
  );
};

export default CreditPackages;