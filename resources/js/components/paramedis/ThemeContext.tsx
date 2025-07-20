import { createContext, useContext, useEffect, useState, ReactNode } from 'react';

type Theme = 'light' | 'dark';

interface ThemeContextType {
  theme: Theme;
  toggleTheme: () => void;
  setTheme: (theme: Theme) => void;
}

const ThemeContext = createContext<ThemeContextType | undefined>(undefined);

export function useTheme() {
  const context = useContext(ThemeContext);
  if (context === undefined) {
    throw new Error('useTheme must be used within a ThemeProvider');
  }
  return context;
}

interface ThemeProviderProps {
  children: ReactNode;
}

export function ThemeProvider({ children }: ThemeProviderProps) {
  const [theme, setThemeState] = useState<Theme>(() => {
    if (typeof window !== 'undefined') {
      // ULTIMATE: Use our completely isolated theme variable
      const dokterkunTheme = (window as any).__DOKTERKU_THEME__;
      if (dokterkunTheme && (dokterkunTheme === 'light' || dokterkunTheme === 'dark')) {
        return dokterkunTheme;
      }
      
      // Ultra-safe fallback to localStorage
      try {
        const savedTheme = localStorage.getItem('theme') as Theme;
        if (savedTheme && (savedTheme === 'light' || savedTheme === 'dark')) {
          return savedTheme;
        }
      } catch (e) {
        // localStorage completely blocked - that's OK
      }
      
      // Ultra-safe system preference fallback
      try {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
          return 'dark';
        }
      } catch (e) {
        // matchMedia completely blocked - that's OK
      }
    }
    return 'light';
  });

  useEffect(() => {
    // Ultra-defensive DOM manipulation with multiple safety checks
    const applyTheme = () => {
      try {
        if (typeof window === 'undefined' || typeof document === 'undefined') {
          return;
        }
        
        // Only touch documentElement - never body to avoid conflicts
        const root = document.documentElement;
        if (root && root.classList) {
          if (theme === 'dark') {
            root.classList.add('dark');
          } else {
            root.classList.remove('dark');
          }
        }
        
        // Safe localStorage access
        try {
          localStorage.setItem('theme', theme);
        } catch (e) {
          // localStorage might be blocked or unavailable
        }
      } catch (e) {
        // Complete safety net - ignore all errors
      }
    };
    
    // Apply theme immediately if DOM is ready, otherwise wait
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', applyTheme, { once: true });
    } else {
      applyTheme();
    }
    
    // Cleanup function
    return () => {
      document.removeEventListener('DOMContentLoaded', applyTheme);
    };
  }, [theme]);

  const setTheme = (newTheme: Theme) => {
    setThemeState(newTheme);
  };

  const toggleTheme = () => {
    setThemeState(theme === 'light' ? 'dark' : 'light');
  };

  return (
    <ThemeContext.Provider value={{ theme, toggleTheme, setTheme }}>
      {children}
    </ThemeContext.Provider>
  );
}