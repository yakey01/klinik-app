import { motion } from 'framer-motion';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';
import { useTheme } from './ThemeContext';
import { 
  Sun, 
  Moon, 
  Eye, 
  Zap, 
  Shield, 
  Smartphone,
  CheckCircle
} from 'lucide-react';

export function DarkModeDemo() {
  const { theme, toggleTheme } = useTheme();
  
  const features = [
    {
      icon: Eye,
      title: 'Enhanced Readability',
      description: 'Improved contrast ratios dan typography untuk better legibility',
      color: 'text-blue-600 dark:text-blue-400'
    },
    {
      icon: Zap,
      title: 'Smooth Transitions',
      description: 'Seamless theme switching dengan enhanced animations',
      color: 'text-yellow-600 dark:text-yellow-400'
    },
    {
      icon: Shield,
      title: 'Accessibility First',
      description: 'WCAG compliant dengan high contrast mode support',
      color: 'text-green-600 dark:text-green-400'
    },
    {
      icon: Smartphone,
      title: 'Mobile Optimized',
      description: 'Touch-friendly interfaces dengan larger tap targets',
      color: 'text-purple-600 dark:text-purple-400'
    }
  ];

  return (
    <motion.div 
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      className="space-y-6 theme-transition"
    >
      {/* Header with Theme Toggle */}
      <Card className="shadow-xl border-0 bg-gradient-to-r from-blue-500 to-purple-600 dark:from-blue-600 dark:to-purple-700 text-white card-enhanced">
        <CardContent className="p-6">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold text-heading-mobile">Dark Mode Enhanced</h1>
              <p className="text-blue-100 dark:text-blue-200 text-mobile-friendly">
                Improved readability & accessibility
              </p>
            </div>
            <Button 
              onClick={toggleTheme}
              variant="secondary"
              size="lg"
              className="bg-white/20 hover:bg-white/30 border-white/30 text-white hover:text-white btn-animate"
            >
              {theme === 'dark' ? (
                <><Sun className="w-5 h-5 mr-2" /> Light Mode</>
              ) : (
                <><Moon className="w-5 h-5 mr-2" /> Dark Mode</>
              )}
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Theme Information */}
      <Card className="card-enhanced card-hover">
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-high-contrast">
            {theme === 'dark' ? (
              <Moon className="w-6 h-6 text-blue-600 dark:text-blue-400" />
            ) : (
              <Sun className="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
            )}
            Current Theme: {theme === 'dark' ? 'Dark Mode' : 'Light Mode'}
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <div className="flex items-center justify-between p-4 bg-muted rounded-lg">
              <span className="text-high-contrast font-medium">Background Color</span>
              <Badge variant="outline" className="font-mono">
                {theme === 'dark' ? 'hsl(224 15% 8%)' : 'hsl(0 0% 100%)'}
              </Badge>
            </div>
            <div className="flex items-center justify-between p-4 bg-muted rounded-lg">
              <span className="text-high-contrast font-medium">Text Color</span>
              <Badge variant="outline" className="font-mono">
                {theme === 'dark' ? 'hsl(210 40% 95%)' : 'hsl(220 13% 10%)'}
              </Badge>
            </div>
            <div className="flex items-center justify-between p-4 bg-muted rounded-lg">
              <span className="text-high-contrast font-medium">Contrast Ratio</span>
              <Badge className="status-success">
                <CheckCircle className="w-3 h-3 mr-1" />
                WCAG AAA
              </Badge>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Features Grid */}
      <div className="grid grid-cols-1 gap-4">
        {features.map((feature, index) => (
          <motion.div
            key={feature.title}
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ delay: index * 0.1 }}
          >
            <Card className="card-enhanced card-hover">
              <CardContent className="p-5">
                <div className="flex items-start gap-4">
                  <div className={`w-12 h-12 rounded-full flex items-center justify-center bg-muted ${feature.color}`}>
                    <feature.icon className="w-6 h-6" />
                  </div>
                  <div className="flex-1">
                    <h3 className="text-lg font-semibold text-high-contrast text-subheading-mobile mb-2">
                      {feature.title}
                    </h3>
                    <p className="text-medium-contrast text-mobile-friendly">
                      {feature.description}
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </motion.div>
        ))}
      </div>

      {/* Typography Showcase */}
      <Card className="card-enhanced">
        <CardHeader>
          <CardTitle className="text-high-contrast">Typography & Readability</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div>
            <h2 className="text-heading-mobile text-high-contrast">Heading Mobile Friendly</h2>
            <p className="text-medium-contrast text-mobile-friendly">
              Responsive typography yang adapts to different screen sizes with optimal contrast ratios.
            </p>
          </div>
          
          <div className="grid grid-cols-3 gap-4">
            <div className="text-center p-4 bg-muted rounded-lg">
              <div className="text-2xl font-bold text-high-contrast">14px</div>
              <div className="text-xs text-medium-contrast">Minimum Size</div>
            </div>
            <div className="text-center p-4 bg-muted rounded-lg">
              <div className="text-2xl font-bold text-high-contrast">16px</div>
              <div className="text-xs text-medium-contrast">Base Size</div>
            </div>
            <div className="text-center p-4 bg-muted rounded-lg">
              <div className="text-2xl font-bold text-high-contrast">48px</div>
              <div className="text-xs text-medium-contrast">Touch Target</div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Status Indicators */}
      <Card className="card-enhanced">
        <CardHeader>
          <CardTitle className="text-high-contrast">Status Indicators</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex flex-wrap gap-3">
            <Badge className="status-success">
              <CheckCircle className="w-3 h-3 mr-1" />
              Active
            </Badge>
            <Badge className="status-warning">
              Pending Review
            </Badge>
            <Badge className="status-danger">
              Needs Attention
            </Badge>
          </div>
        </CardContent>
      </Card>
    </motion.div>
  );
}