// Kimi K2 Command for Claude Code
// Usage: /kimi <question>

const KIMI_API_KEY = process.env.KIMI_API_KEY || "sk-CJkORBXMnE67cIu7K7vTycBS49iu4eDylnEcnC5a6BlAMHzx";
const KIMI_BASE_URL = "https://api.moonshot.cn/v1";

async function callKimiAPI(question) {
    try {
        const response = await fetch(`${KIMI_BASE_URL}/chat/completions`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${KIMI_API_KEY}`
            },
            body: JSON.stringify({
                model: 'kimi',
                messages: [
                    {
                        role: 'user',
                        content: question
                    }
                ],
                stream: false
            })
        });

        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error.message);
        }
        
        return data.choices[0].message.content;
    } catch (error) {
        return `Error: ${error.message}`;
    }
}

// Export for Claude Code
module.exports = {
    name: 'kimi',
    description: 'Use Kimi K2 model for questions',
    execute: async (args) => {
        const question = args.join(' ');
        if (!question) {
            return 'Please provide a question. Usage: /kimi <question>';
        }
        
        return await callKimiAPI(question);
    }
}; 