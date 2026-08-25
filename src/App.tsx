/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState } from 'react';
import { Bot, Code2, Copy, CheckCircle2, Rocket, ExternalLink, Link2 } from 'lucide-react';

export default function App() {
  const [vercelUrl, setVercelUrl] = useState('');
  const [webhookStatus, setWebhookStatus] = useState<{ type: 'success' | 'error', msg: string } | null>(null);
  const botToken = "7948122316:AAHSTsu0-rVnCVuaCuli1kUoAlkcgdz2NdI";

  const handleSetWebhook = async () => {
    if (!vercelUrl) {
      setWebhookStatus({ type: 'error', msg: 'Please enter your Vercel App URL first.' });
      return;
    }
    
    // Ensure URL is properly formatted
    let finalUrl = vercelUrl.trim();
    if (!finalUrl.startsWith('http')) {
      finalUrl = 'https://' + finalUrl;
    }
    // Remove trailing slashes
    finalUrl = finalUrl.replace(/\/+$/, '');

    try {
      const response = await fetch(`https://api.telegram.org/bot${botToken}/setWebhook?url=${finalUrl}/api/webhook.php`);
      const data = await response.json();
      
      if (data.ok) {
        setWebhookStatus({ type: 'success', msg: 'Webhook successfully set! Your bot is now live.' });
      } else {
        setWebhookStatus({ type: 'error', msg: `Telegram Error: ${data.description}` });
      }
    } catch (err) {
      setWebhookStatus({ type: 'error', msg: 'Failed to connect to Telegram API. Check your internet connection.' });
    }
  };

  const copyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text);
    alert("Code copied to clipboard!");
  };

  return (
    <div className="min-h-screen bg-slate-50 text-slate-800 p-8 font-sans">
      <div className="max-w-4xl mx-auto space-y-8">
        
        {/* Header */}
        <div className="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center space-y-4">
          <div className="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <Bot size={32} />
          </div>
          <h1 className="text-3xl font-bold tracking-tight text-slate-900">Telegram Chat ID Bot Dashboard</h1>
          <p className="text-slate-500 max-w-xl mx-auto text-lg">
            Your PHP bot code and Vercel configuration are ready. Follow the steps below to deploy and activate your bot.
          </p>
        </div>

        {/* Step 1: Export */}
        <div className="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 space-y-4">
          <div className="flex items-center space-x-3 text-blue-600 mb-2">
            <div className="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center font-bold text-sm">1</div>
            <h2 className="text-xl font-bold text-slate-900">Deploy to Vercel</h2>
          </div>
          <p className="text-slate-600">
            Click the <strong className="text-slate-900">Export / Share</strong> menu at the top right of this editor and select <strong className="text-slate-900">Export to GitHub</strong>.
            Once pushed to GitHub, go to <a href="https://vercel.com/new" target="_blank" rel="noreferrer" className="text-blue-600 hover:underline">Vercel</a> and import the repository.
          </p>
          <div className="mt-4 p-4 bg-slate-50 rounded-xl border border-slate-100 flex items-start space-x-4">
            <Code2 className="text-slate-400 mt-1" size={20} />
            <div>
              <h3 className="font-semibold text-slate-800">Included Files:</h3>
              <ul className="text-sm text-slate-500 list-disc list-inside mt-2 space-y-1">
                <li><code className="bg-white px-2 py-0.5 rounded border border-slate-200">api/webhook.php</code> (Core Bot Logic & API)</li>
                <li><code className="bg-white px-2 py-0.5 rounded border border-slate-200">vercel.json</code> (Vercel PHP Serverless Runtime Config)</li>
              </ul>
            </div>
          </div>
        </div>

        {/* Step 2: Set Webhook */}
        <div className="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 space-y-4">
          <div className="flex items-center space-x-3 text-emerald-600 mb-2">
            <div className="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center font-bold text-sm">2</div>
            <h2 className="text-xl font-bold text-slate-900">Activate Webhook</h2>
          </div>
          <p className="text-slate-600">
            After Vercel finishes deploying, copy your live project URL (e.g., <code>https://my-telegram-bot.vercel.app</code>) and paste it below. We will tell Telegram to send messages to this URL.
          </p>
          
          <div className="bg-slate-50 p-6 rounded-xl border border-slate-200 space-y-4 mt-4">
            <div className="space-y-2">
              <label className="text-sm font-semibold text-slate-700 flex items-center">
                <Link2 size={16} className="mr-2" />
                Vercel Application URL
              </label>
              <div className="flex space-x-3">
                <input 
                  type="text" 
                  value={vercelUrl}
                  onChange={(e) => setVercelUrl(e.target.value)}
                  placeholder="https://your-app-name.vercel.app"
                  className="flex-1 bg-white border border-slate-300 rounded-lg px-4 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
                />
                <button 
                  onClick={handleSetWebhook}
                  className="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded-lg font-medium transition flex items-center shadow-sm"
                >
                  <Rocket size={18} className="mr-2" />
                  Set Webhook
                </button>
              </div>
            </div>

            {webhookStatus && (
              <div className={`p-4 rounded-lg flex items-start space-x-3 ${webhookStatus.type === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'}`}>
                {webhookStatus.type === 'success' ? <CheckCircle2 className="mt-0.5 shrink-0" size={18} /> : <AlertCircle className="mt-0.5 shrink-0" size={18} />}
                <p className="text-sm font-medium">{webhookStatus.msg}</p>
              </div>
            )}
          </div>
        </div>

      </div>
    </div>
  );
}
