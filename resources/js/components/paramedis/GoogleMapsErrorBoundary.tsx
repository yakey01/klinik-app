import React, { Component, ErrorInfo, ReactNode } from 'react';
import { Button } from '../ui/button';
import { AlertTriangle, RefreshCw } from 'lucide-react';

interface Props {
  children: ReactNode;
}

interface State {
  hasError: boolean;
  error?: Error;
}

class GoogleMapsErrorBoundary extends Component<Props, State> {
  constructor(props: Props) {
    super(props);
    this.state = { hasError: false };
  }

  public static getDerivedStateFromError(error: Error): State {
    // Update state so the next render will show the fallback UI
    return { hasError: true, error };
  }

  public componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    console.error('Google Maps Error:', error, errorInfo);
  }

  public render() {
    if (this.state.hasError) {
      return (
        <div className="flex flex-col items-center justify-center p-8 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
          <AlertTriangle className="w-12 h-12 text-orange-500 mb-4" />
          <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
            Google Maps Error
          </h3>
          <p className="text-sm text-gray-600 dark:text-gray-300 mb-4 text-center">
            Terjadi kesalahan saat memuat peta. Silakan coba lagi.
          </p>
          <Button
            onClick={() => this.setState({ hasError: false, error: undefined })}
            className="bg-blue-600 hover:bg-blue-700 text-white"
            size="sm"
          >
            <RefreshCw className="w-4 h-4 mr-2" />
            Coba Lagi
          </Button>
          {process.env.NODE_ENV === 'development' && this.state.error && (
            <details className="mt-4 p-3 bg-red-50 dark:bg-red-950/30 rounded border text-xs">
              <summary className="cursor-pointer text-red-700 dark:text-red-300 font-medium">
                Error Details (Development Only)
              </summary>
              <pre className="mt-2 text-red-600 dark:text-red-400 whitespace-pre-wrap">
                {this.state.error.message}
                {this.state.error.stack}
              </pre>
            </details>
          )}
        </div>
      );
    }

    return this.props.children;
  }
}

export default GoogleMapsErrorBoundary;