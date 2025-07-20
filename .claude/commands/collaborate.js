// Claude + Kimi K2 Collaboration Command
// Usage: /collaborate <action> [params]

import { execSync } from 'child_process';
import fs from 'fs';

const KIMI_API_KEY = process.env.KIMI_API_KEY || "sk-m9J3djtqPimBF9ZXZq91hPnyrCtcsK6T56zFnMrIfal6G4Lp";
const KIMI_BASE_URL = process.env.KIMI_BASE_URL || "https://api.moonshot.ai/v1";

// Function to call Kimi K2 API
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

// Function to analyze current file with Kimi
async function analyzeCurrentFile(analysisType) {
    try {
        // Get current file from Claude Code context
        const currentFile = process.env.CLAUDE_CURRENT_FILE || 'app/Models/User.php';
        
        if (!fs.existsSync(currentFile)) {
            return `File tidak ditemukan: ${currentFile}`;
        }
        
        const fileContent = fs.readFileSync(currentFile, 'utf8');
        
        let prompt = '';
        switch (analysisType) {
            case 'security':
                prompt = `Analisis keamanan file PHP ini dan berikan 3 saran perbaikan yang paling penting:\n\n${fileContent}`;
                break;
            case 'performance':
                prompt = `Analisis performa file PHP ini dan berikan 3 saran optimasi:\n\n${fileContent}`;
                break;
            case 'best_practices':
                prompt = `Analisis best practices file PHP ini dan berikan 3 saran perbaikan:\n\n${fileContent}`;
                break;
            case 'bugs':
                prompt = `Cari 3 bug atau masalah potensial dalam file PHP ini:\n\n${fileContent}`;
                break;
            default:
                prompt = `Analisis file PHP ini dan berikan 3 saran perbaikan:\n\n${fileContent}`;
        }
        
        console.log(`🔍 Kimi menganalisis: ${currentFile} (${analysisType})`);
        return await callKimiAPI(prompt);
        
    } catch (error) {
        return `Error analyzing file: ${error.message}`;
    }
}

// Function to get Kimi's opinion on Claude's work
async function getKimiOpinion(claudeWork) {
    const prompt = `Berikut adalah hasil kerja dari Claude Code. Berikan pendapat dan saran tambahan:\n\n${claudeWork}`;
    return await callKimiAPI(prompt);
}

// Function to collaborative code review
async function collaborativeReview() {
    try {
        const currentFile = process.env.CLAUDE_CURRENT_FILE || 'app/Models/User.php';
        
        if (!fs.existsSync(currentFile)) {
            return `File tidak ditemukan: ${currentFile}`;
        }
        
        const fileContent = fs.readFileSync(currentFile, 'utf8');
        
        let result = `🔍 Collaborative Code Review: ${currentFile}\n`;
        result += `======================================\n\n`;
        
        // Step 1: Kimi analyzes
        result += `📋 Step 1: Kimi melakukan analisis awal...\n`;
        const analysis = await callKimiAPI(`Analisis best practices file PHP ini:\n\n${fileContent}`);
        result += `${analysis}\n\n`;
        
        // Step 2: Get specific improvements
        result += `📋 Step 2: Kimi memberikan saran spesifik...\n`;
        const improvements = await callKimiAPI(`Berdasarkan analisis sebelumnya, berikan 3 saran perbaikan yang paling penting untuk file ini`);
        result += `${improvements}\n\n`;
        
        // Step 3: Implementation guidance
        result += `📋 Step 3: Kimi memberikan panduan implementasi...\n`;
        const guidance = await callKimiAPI(`Berikan contoh kode untuk mengimplementasikan saran perbaikan tersebut`);
        result += `${guidance}\n\n`;
        
        result += `✅ Collaborative review selesai!\n`;
        result += `💡 Sekarang Anda bisa menggunakan Claude Code untuk mengimplementasikan saran Kimi`;
        
        return result;
        
    } catch (error) {
        return `Error in collaborative review: ${error.message}`;
    }
}

// Export for Claude Code
export default {
    name: 'collaborate',
    description: 'Collaborate with Kimi K2 for code analysis and improvements',
    execute: async (args) => {
        if (args.length === 0) {
            return `Usage: /collaborate <action> [params]
            
Actions:
- analyze <type> - Analyze current file (security|performance|best_practices|bugs)
- review - Full collaborative code review
- ask <question> - Ask Kimi a question
- opinion <text> - Get Kimi's opinion on Claude's work

Examples:
- /collaborate analyze security
- /collaborate review
- /collaborate ask "What is Laravel?"
- /collaborate opinion "Claude suggested using validation rules"`;
        }
        
        const action = args[0];
        const params = args.slice(1);
        
        switch (action) {
            case 'analyze':
                const analysisType = params[0] || 'best_practices';
                return await analyzeCurrentFile(analysisType);
                
            case 'review':
                return await collaborativeReview();
                
            case 'ask':
                const question = params.join(' ');
                if (!question) {
                    return 'Please provide a question. Usage: /collaborate ask <question>';
                }
                return await callKimiAPI(question);
                
            case 'opinion':
                const text = params.join(' ');
                if (!text) {
                    return 'Please provide text. Usage: /collaborate opinion <text>';
                }
                return await getKimiOpinion(text);
                
            default:
                return `Unknown action: ${action}. Use /collaborate for help.`;
        }
    }
}; 