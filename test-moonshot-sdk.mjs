import { Moonshot } from 'moonshot-node';

// Initialize Moonshot client
const moonshot = new Moonshot({
    apiKey: 'sk-CJkORBXMnE67cIu7K7vTycBS49iu4eDylnEcnC5a6BlAMHzx'
});

async function testMoonshotAPI() {
    try {
        console.log('🧪 Testing Moonshot API with official SDK...');
        
        // Test chat completion
        const response = await moonshot.chat.completions.create({
            model: 'moonshot-v1-8k',
            messages: [
                {
                    role: 'user',
                    content: 'Hello, test connection'
                }
            ]
        });
        
        console.log('✅ Success!');
        console.log('Response:', response.choices[0].message.content);
        
    } catch (error) {
        console.error('❌ Error:', error.message);
        
        // Try different models
        const models = ['moonshot-v1-8k', 'moonshot-v1-32k', 'moonshot-v1-128k', 'kimi'];
        
        for (const model of models) {
            try {
                console.log(`\n🔄 Trying model: ${model}`);
                const response = await moonshot.chat.completions.create({
                    model: model,
                    messages: [
                        {
                            role: 'user',
                            content: 'Hello'
                        }
                    ]
                });
                
                console.log(`✅ ${model} works!`);
                console.log('Response:', response.choices[0].message.content);
                break;
                
            } catch (modelError) {
                console.log(`❌ ${model}: ${modelError.message}`);
            }
        }
    }
}

// Run the test
testMoonshotAPI(); 