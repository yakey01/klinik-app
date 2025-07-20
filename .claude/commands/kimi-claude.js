// Kimi K2 Command for Claude Code
// Usage: /kimi <question>

import { execSync } from 'child_process';

const KIMI_API_KEY = process.env.KIMI_API_KEY || "sk-m9J3djtqPimBF9ZXZq91hPnyrCtcsK6T56zFnMrIfal6G4Lp";
const KIMI_BASE_URL = process.env.KIMI_BASE_URL || "https://api.moonshot.ai/v1";

async function callKimiAPI(question) {
    try {
        const response = await fetch(`${KIMI_BASE_URL}/chat/completions`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${KIMI_API_KEY}`
            },
            body: JSON.stringify({
                model: 'moonshot-v1-8k',
                messages: [
                    {
                        role: 'user',
                        content: question
                    }
                ],
                temperature: 0.7,
                max_tokens: 500
            })
        });

        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error.message);
        }
        
        return data.choices[0].message.content;
    } catch (error) {
        return `Error calling Kimi K2: ${error.message}`;
    }
}

// Alternative method using shell script
function callKimiViaScript(question) {
    try {
        const result = execSync(`./test-kimi-quick.sh "${question}"`, { 
            encoding: 'utf8',
            cwd: process.cwd()
        });
        return result;
    } catch (error) {
        return `Error: ${error.message}`;
    }
}

// Export for Claude Code
export default {
    name: 'kimi',
    description: 'Use Kimi K2 model for questions (faster than Claude)',
    execute: async (args) => {
        const question = args.join(' ');
        if (!question) {
            return 'Please provide a question. Usage: /kimi <question>';
        }
        
        console.log(`🤖 Calling Kimi K2: ${question}`);
        
        // Try direct API call first, fallback to script
        try {
            const response = await callKimiAPI(question);
            return `**Kimi K2 Response:**\n\n${response}`;
        } catch (error) {
            console.log('Falling back to script method...');
            return callKimiViaScript(question);
        }
    }
}; 