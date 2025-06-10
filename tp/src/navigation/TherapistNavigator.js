import React from 'react';
import {createBottomTabNavigator} from '@react-navigation/bottom-tabs';
import {useTheme} from 'react-native-paper';
import Icon from 'react-native-vector-icons/MaterialIcons';
import TherapistDashboardScreen from '../screens/therapist/TherapistDashboardScreen';
import TherapistPatientsScreen from '../screens/therapist/TherapistPatientsScreen';
import TherapistCalendarScreen from '../screens/therapist/TherapistCalendarScreen';
import TherapistProfileScreen from '../screens/therapist/TherapistProfileScreen';

const Tab = createBottomTabNavigator();

const TherapistNavigator = () => {
  const theme = useTheme();

  return (
    <Tab.Navigator
      screenOptions={({route}) => ({
        tabBarIcon: ({focused, color, size}) => {
          let iconName;

          if (route.name === 'Home') {
            iconName = 'dashboard';
          } else if (route.name === 'Patients') {
            iconName = 'people';
          } else if (route.name === 'Calendar') {
            iconName = 'schedule';
          } else if (route.name === 'Profile') {
            iconName = 'person';
          }

          return <Icon name={iconName} size={size} color={color} />;
        },
        tabBarActiveTintColor: theme.colors.secondary,
        tabBarInactiveTintColor: theme.colors.onSurfaceVariant,
        tabBarStyle: {
          backgroundColor: theme.colors.surface,
          borderTopWidth: 1,
          borderTopColor: theme.colors.outline,
          height: 60,
          paddingBottom: 8,
          paddingTop: 8,
        },
        headerShown: false,
      })}>
      <Tab.Screen
        name="Home"
        component={TherapistDashboardScreen}
        options={{title: 'Dashboard'}}
      />
      <Tab.Screen
        name="Patients"
        component={TherapistPatientsScreen}
        options={{title: 'Pazienti'}}
      />
      <Tab.Screen
        name="Calendar"
        component={TherapistCalendarScreen}
        options={{title: 'Agenda'}}
      />
      <Tab.Screen
        name="Profile"
        component={TherapistProfileScreen}
        options={{title: 'Profilo'}}
      />
    </Tab.Navigator>
  );
};

export default TherapistNavigator;
