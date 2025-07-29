#!/bin/bash

# Dokterku Server Management Script

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
NC='\033[0m' # No Color

case "$1" in
    start)
        echo -e "${GREEN}Starting Dokterku servers...${NC}"
        
        # Kill any existing servers on our ports
        echo "Cleaning up old processes..."
        lsof -ti:8000 | xargs kill -9 2>/dev/null
        lsof -ti:5173 | xargs kill -9 2>/dev/null
        
        sleep 1
        
        # Start Laravel server
        echo "Starting Laravel server on port 8000..."
        php artisan serve > /tmp/laravel-server.log 2>&1 &
        echo $! > /tmp/laravel.pid
        
        # Start Vite server
        echo "Starting Vite dev server on port 5173..."
        npm run dev > /tmp/vite-server.log 2>&1 &
        echo $! > /tmp/vite.pid
        
        sleep 3
        
        # Verify servers started
        if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null ; then
            echo -e "${GREEN}✓ Laravel server running on http://localhost:8000${NC}"
        else
            echo -e "${RED}✗ Laravel server failed to start${NC}"
        fi
        
        if lsof -Pi :5173 -sTCP:LISTEN -t >/dev/null ; then
            echo -e "${GREEN}✓ Vite server running on http://localhost:5173${NC}"
        else
            echo -e "${RED}✗ Vite server failed to start${NC}"
        fi
        ;;
        
    stop)
        echo -e "${YELLOW}Stopping Dokterku servers...${NC}"
        
        # Kill servers using PID files
        if [ -f /tmp/laravel.pid ]; then
            kill $(cat /tmp/laravel.pid) 2>/dev/null
            rm /tmp/laravel.pid
        fi
        
        if [ -f /tmp/vite.pid ]; then
            kill $(cat /tmp/vite.pid) 2>/dev/null
            rm /tmp/vite.pid
        fi
        
        # Kill any remaining processes on ports
        lsof -ti:8000 | xargs kill -9 2>/dev/null
        lsof -ti:5173 | xargs kill -9 2>/dev/null
        
        echo -e "${GREEN}✓ Servers stopped${NC}"
        ;;
        
    restart)
        $0 stop
        sleep 2
        $0 start
        ;;
        
    status)
        echo -e "${GREEN}Dokterku Server Status:${NC}"
        echo "========================"
        
        if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null ; then
            echo -e "Laravel: ${GREEN}● Running${NC} on http://localhost:8000"
        else
            echo -e "Laravel: ${RED}● Stopped${NC}"
        fi
        
        if lsof -Pi :5173 -sTCP:LISTEN -t >/dev/null ; then
            echo -e "Vite:    ${GREEN}● Running${NC} on http://localhost:5173"
        else
            echo -e "Vite:    ${RED}● Stopped${NC}"
        fi
        
        echo ""
        echo "Panel URLs:"
        echo "- Admin:     http://localhost:8000/admin/login"
        echo "- Bendahara: http://localhost:8000/bendahara/login"
        echo "- Dokter:    http://localhost:8000/dokter/login"
        echo "- Manajer:   http://localhost:8000/manajer/login"
        echo "- Paramedis: http://localhost:8000/paramedis/login"
        echo "- Petugas:   http://localhost:8000/petugas/login"
        ;;
        
    *)
        echo "Usage: $0 {start|stop|restart|status}"
        exit 1
        ;;
esac