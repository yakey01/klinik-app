import React from 'react';
import { createStackNavigator } from '@react-navigation/stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { 
  Home, 
  Clock, 
  DollarSign, 
  MapPin, 
  Activity 
} from 'lucide-react-native';

// Screens
import DashboardScreen from '../screens/DashboardScreen';
import JaspelScreen from '../screens/JaspelScreen';
import PresensiScreen from '../screens/PresensiScreen';
import GPSScreen from '../screens/GPSScreen';
import TindakanScreen from '../screens/TindakanScreen';

const Stack = createStackNavigator();
const Tab = createBottomTabNavigator();

// Bottom Tab Navigator sesuai dengan existing design
const BottomTabNavigator = () => {
  return (
    <Tab.Navigator
      screenOptions={{
        headerShown: false,
        tabBarStyle: {
          backgroundColor: '#FFFFFF',
          borderTopWidth: 1,
          borderTopColor: '#E5E7EB',
          paddingTop: 8,
          paddingBottom: 28, // Safe area untuk iPhone
          height: 88,
          elevation: 8,
          shadowColor: '#000',
          shadowOffset: { width: 0, height: -2 },
          shadowOpacity: 0.1,
          shadowRadius: 8,
        },
        tabBarActiveTintColor: '#3B82F6',
        tabBarInactiveTintColor: '#6B7280',
        tabBarLabelStyle: {
          fontSize: 11,
          fontWeight: '500',
          fontFamily: 'Inter-Medium',
          marginTop: 4,
        },
        tabBarIconStyle: {
          marginTop: 4,
        },
      }}
    >
      <Tab.Screen
        name="Dashboard"
        component={DashboardScreen}
        options={{
          tabBarLabel: 'Beranda',
          tabBarIcon: ({ color, size }) => (
            <Home size={size} color={color} />
          ),
        }}
      />
      <Tab.Screen
        name="Presensi"
        component={PresensiScreen}
        options={{
          tabBarLabel: 'Presensi', 
          tabBarIcon: ({ color, size }) => (
            <Clock size={size} color={color} />
          ),
        }}
      />
      <Tab.Screen
        name="Jaspel"
        component={JaspelStackNavigator}
        options={{
          tabBarLabel: 'Jaspel',
          tabBarIcon: ({ color, size }) => (
            <DollarSign size={size} color={color} />
          ),
        }}
      />
      <Tab.Screen
        name="GPS"
        component={GPSScreen}
        options={{
          tabBarLabel: 'GPS',
          tabBarIcon: ({ color, size }) => (
            <MapPin size={size} color={color} />
          ),
        }}
      />
      <Tab.Screen
        name="Tindakan"
        component={TindakanScreen}
        options={{
          tabBarLabel: 'Tindakan',
          tabBarIcon: ({ color, size }) => (
            <Activity size={size} color={color} />
          ),
        }}
      />
    </Tab.Navigator>
  );
};

// Stack Navigator untuk Jaspel (memungkinkan detail view dan back navigation)
const JaspelStackNavigator = () => {
  return (
    <Stack.Navigator
      screenOptions={{
        headerShown: false,
        cardStyleInterpolator: ({ current, next, layouts }) => {
          return {
            cardStyle: {
              transform: [
                {
                  translateX: current.progress.interpolate({
                    inputRange: [0, 1],
                    outputRange: [layouts.screen.width, 0],
                  }),
                },
              ],
            },
            overlayStyle: {
              opacity: current.progress.interpolate({
                inputRange: [0, 1],
                outputRange: [0, 0.5],
              }),
            },
          };
        },
      }}
    >
      <Stack.Screen
        name="JaspelMain"
        component={JaspelScreen}
        options={{
          title: 'Jaspel Saya',
        }}
      />
      {/* Bisa ditambahkan screen detail jaspel di sini */}
      {/*
      <Stack.Screen
        name="JaspelDetail"
        component={JaspelDetailScreen}
        options={{
          title: 'Detail Jaspel',
        }}
      />
      */}
    </Stack.Navigator>
  );
};

// Main Paramedis Navigator
const ParamedisNavigator = () => {
  return (
    <Stack.Navigator
      screenOptions={{
        headerShown: false,
      }}
    >
      <Stack.Screen
        name="ParamedisMain"
        component={BottomTabNavigator}
      />
    </Stack.Navigator>
  );
};

export default ParamedisNavigator;