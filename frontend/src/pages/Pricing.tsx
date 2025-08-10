import React, { useState, useEffect } from 'react';
import { useAuth } from '../hooks/useAuth';
import { ApiService } from '../services/api';
import { 
  CheckIcon,
  CreditCardIcon,
  BanknotesIcon,
  BuildingLibraryIcon,
  SparklesIcon,
  StarIcon
} from '@heroicons/react/24/outline';

interface CreditPackage {
  id: number;
  name: string;
  description: string;
  credits: number;
  price_cents: number;
  currency: string;
  tier: number;
  features: string[];
  is_popular: boolean;
  discount_percentage: number;
  sale_ends_at?: string;
}

const Pricing: React.FC = () => {
  const { user, isAuthenticated } = useAuth();
  const [packages, setPackages] = useState<CreditPackage[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedPackage, setSelectedPackage] = useState<CreditPackage | null>(null);
  const [paymentMethod, setPaymentMethod] = useState<'stripe' | 'paypal' | 'bank'>('stripe');
  const [showPaymentModal, setShowPaymentModal] = useState(false);

  useEffect(() => {
    loadPackages();
  }, []);

  const loadPackages = async () => {
    try {
      const response = await ApiService.get('/credit-packages');
      if (response.success) {
        setPackages(response.data);
      }
    } catch (error) {
      console.error('Failed to load packages:', error);
    } finally {
      setLoading(false);
    }
  };

  const handlePurchase = (pkg: CreditPackage) => {
    if (!isAuthenticated) {
      alert('Please sign in to purchase credits');
      return;
    }

    setSelectedPackage(pkg);
    setShowPaymentModal(true);
  };

  const processPayment = async () => {
    if (!selectedPackage) return;

    try {
      let response;
      
      switch (paymentMethod) {
        case 'stripe':
          response = await ApiService.post('/payments/stripe/create-intent', {
            credit_package_id: selectedPackage.id,
          });
          
          if (response.success) {
            // Initialize Stripe checkout
            // This would integrate with Stripe Elements
            window.location.href = `/checkout/stripe?intent=${response.data.client_secret}`;
          }
          break;

        case 'paypal':
          response = await ApiService.post('/payments/paypal/create-order', {
            credit_package_id: selectedPackage.id,
          });
          
          if (response.success) {
            window.location.href = response.data.approval_url;
          }
          break;

        case 'bank':
          response = await ApiService.post('/payments/bank-deposit', {
            credit_package_id: selectedPackage.id,
          });
          
          if (response.success) {
            window.location.href = `/payment/bank-deposit/${response.data.transaction_id}`;
          }
          break;
      }
    } catch (error) {
      console.error('Payment failed:', error);
      alert('Payment failed. Please try again.');
    }
  };

  const formatPrice = (cents: number, currency: string) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency,
    }).format(cents / 100);
  };

  const getDiscountedPrice = (pkg: CreditPackage) => {
    if (pkg.discount_percentage > 0) {
      return pkg.price_cents * (1 - pkg.discount_percentage / 100);
    }
    return pkg.price_cents;
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div className="text-center">
            <h1 className="text-4xl font-bold text-gray-900 mb-4">
              <SparklesIcon className="h-10 w-10 inline mr-3 text-primary-600" />
              Choose Your Credits Package
            </h1>
            <p className="text-xl text-gray-600 max-w-3xl mx-auto">
              Power your AI conversations with flexible credit packages. 
              Pay only for what you use, no monthly subscriptions.
            </p>
          </div>
        </div>
      </div>

      {/* Current Balance (if logged in) */}
      {isAuthenticated && (
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div className="bg-primary-50 border border-primary-200 rounded-lg p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-primary-800 font-medium">Current Balance</p>
                <p className="text-2xl font-bold text-primary-900">
                  {user?.credits_balance?.toLocaleString() || 0} credits
                </p>
              </div>
              <div className="text-primary-600">
                <CreditCardIcon className="h-8 w-8" />
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Pricing Grid */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {packages.map((pkg) => (
            <div
              key={pkg.id}
              className={`relative bg-white rounded-lg shadow-sm border-2 overflow-hidden ${
                pkg.is_popular ? 'border-primary-500' : 'border-gray-200'
              }`}
            >
              {/* Popular Badge */}
              {pkg.is_popular && (
                <div className="absolute -top-3 left-1/2 transform -translate-x-1/2">
                  <div className="bg-primary-600 text-white px-4 py-1 rounded-full text-sm font-semibold flex items-center">
                    <StarIcon className="h-4 w-4 mr-1" />
                    Most Popular
                  </div>
                </div>
              )}

              {/* Sale Badge */}
              {pkg.discount_percentage > 0 && (
                <div className="absolute top-4 right-4">
                  <div className="bg-red-500 text-white px-2 py-1 rounded text-xs font-bold">
                    {pkg.discount_percentage}% OFF
                  </div>
                </div>
              )}

              <div className="p-6">
                {/* Package Header */}
                <div className="text-center mb-6">
                  <h3 className="text-xl font-bold text-gray-900 mb-2">{pkg.name}</h3>
                  <p className="text-gray-600 text-sm mb-4">{pkg.description}</p>
                  
                  {/* Pricing */}
                  <div className="mb-4">
                    {pkg.discount_percentage > 0 ? (
                      <div>
                        <span className="text-2xl font-bold text-gray-400 line-through">
                          {formatPrice(pkg.price_cents, pkg.currency)}
                        </span>
                        <span className="text-3xl font-bold text-gray-900 ml-2">
                          {formatPrice(getDiscountedPrice(pkg), pkg.currency)}
                        </span>
                      </div>
                    ) : (
                      <span className="text-3xl font-bold text-gray-900">
                        {formatPrice(pkg.price_cents, pkg.currency)}
                      </span>
                    )}
                  </div>
                  
                  {/* Credits */}
                  <div className="text-primary-600 font-semibold">
                    {pkg.credits.toLocaleString()} Credits
                  </div>
                  
                  {/* Value */}
                  <div className="text-sm text-gray-500 mt-1">
                    ${(pkg.price_cents / 100 / pkg.credits * 1000).toFixed(3)} per 1,000 credits
                  </div>
                </div>

                {/* Features */}
                <ul className="space-y-2 mb-6">
                  {pkg.features.map((feature, index) => (
                    <li key={index} className="flex items-start">
                      <CheckIcon className="h-4 w-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span className="text-sm text-gray-600">{feature}</span>
                    </li>
                  ))}
                </ul>

                {/* Tier Badge */}
                {pkg.tier > 1 && (
                  <div className="mb-4">
                    <span className="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-800">
                      VIP Tier {pkg.tier}
                    </span>
                  </div>
                )}

                {/* Purchase Button */}
                <button
                  onClick={() => handlePurchase(pkg)}
                  className={`w-full py-3 px-4 rounded-lg font-semibold ${
                    pkg.is_popular
                      ? 'bg-primary-600 hover:bg-primary-700 text-white'
                      : 'bg-gray-100 hover:bg-gray-200 text-gray-900'
                  }`}
                >
                  Purchase Credits
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Payment Methods Info */}
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4 text-center">
            Multiple Payment Options Available
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="text-center">
              <CreditCardIcon className="h-8 w-8 text-primary-600 mx-auto mb-2" />
              <h4 className="font-medium text-gray-900 mb-1">Credit Card</h4>
              <p className="text-sm text-gray-600">Instant processing via Stripe</p>
            </div>
            <div className="text-center">
              <BanknotesIcon className="h-8 w-8 text-blue-600 mx-auto mb-2" />
              <h4 className="font-medium text-gray-900 mb-1">PayPal</h4>
              <p className="text-sm text-gray-600">Secure PayPal payments</p>
            </div>
            <div className="text-center">
              <BuildingLibraryIcon className="h-8 w-8 text-green-600 mx-auto mb-2" />
              <h4 className="font-medium text-gray-900 mb-1">Bank Transfer</h4>
              <p className="text-sm text-gray-600">Manual approval within 24h</p>
            </div>
          </div>
        </div>
      </div>

      {/* Payment Modal */}
      {showPaymentModal && selectedPackage && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg max-w-md w-full mx-4 p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">
              Purchase {selectedPackage.name}
            </h3>
            
            <div className="mb-6">
              <div className="bg-gray-50 rounded-lg p-4">
                <div className="flex justify-between items-center mb-2">
                  <span className="text-gray-600">Credits:</span>
                  <span className="font-semibold">{selectedPackage.credits.toLocaleString()}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-gray-600">Total:</span>
                  <span className="text-xl font-bold text-gray-900">
                    {formatPrice(getDiscountedPrice(selectedPackage), selectedPackage.currency)}
                  </span>
                </div>
              </div>
            </div>

            {/* Payment Method Selection */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-3">
                Choose Payment Method
              </label>
              <div className="space-y-2">
                <label className="flex items-center">
                  <input
                    type="radio"
                    name="payment"
                    value="stripe"
                    checked={paymentMethod === 'stripe'}
                    onChange={(e) => setPaymentMethod(e.target.value as 'stripe')}
                    className="mr-3"
                  />
                  <CreditCardIcon className="h-5 w-5 mr-2 text-gray-400" />
                  Credit Card (Instant)
                </label>
                <label className="flex items-center">
                  <input
                    type="radio"
                    name="payment"
                    value="paypal"
                    checked={paymentMethod === 'paypal'}
                    onChange={(e) => setPaymentMethod(e.target.value as 'paypal')}
                    className="mr-3"
                  />
                  <BanknotesIcon className="h-5 w-5 mr-2 text-gray-400" />
                  PayPal (Instant)
                </label>
                <label className="flex items-center">
                  <input
                    type="radio"
                    name="payment"
                    value="bank"
                    checked={paymentMethod === 'bank'}
                    onChange={(e) => setPaymentMethod(e.target.value as 'bank')}
                    className="mr-3"
                  />
                  <BuildingLibraryIcon className="h-5 w-5 mr-2 text-gray-400" />
                  Bank Transfer (24h approval)
                </label>
              </div>
            </div>

            {/* Actions */}
            <div className="flex gap-3">
              <button
                onClick={() => setShowPaymentModal(false)}
                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                onClick={processPayment}
                className="flex-1 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium"
              >
                Continue to Payment
              </button>
            </div>
          </div>
        </div>
      )}

      {/* FAQ Section */}
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
          <h3 className="text-2xl font-bold text-gray-900 mb-6 text-center">
            Frequently Asked Questions
          </h3>
          
          <div className="space-y-6">
            <div>
              <h4 className="font-semibold text-gray-900 mb-2">How do credits work?</h4>
              <p className="text-gray-600">
                Credits are consumed based on the length of AI responses. Approximately 1 credit per character. 
                A typical response uses 100-500 credits.
              </p>
            </div>
            
            <div>
              <h4 className="font-semibold text-gray-900 mb-2">Do credits expire?</h4>
              <p className="text-gray-600">
                No, your credits never expire. Use them at your own pace.
              </p>
            </div>
            
            <div>
              <h4 className="font-semibold text-gray-900 mb-2">What payment methods do you accept?</h4>
              <p className="text-gray-600">
                We accept credit cards (via Stripe), PayPal, and bank transfers. 
                Card and PayPal payments are processed instantly, while bank transfers require manual approval.
              </p>
            </div>
            
            <div>
              <h4 className="font-semibold text-gray-900 mb-2">Can I get a refund?</h4>
              <p className="text-gray-600">
                Yes, we offer refunds for unused credits within 30 days of purchase. 
                Contact our support team for assistance.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Pricing;