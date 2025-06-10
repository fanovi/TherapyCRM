import React from 'react';
import {createBottomTabNavigator} from '@react-navigation/bottom-tabs';
import {useTheme} from 'react-native-paper';
import Icon from 'react-native-vector-icons/MaterialIcons';
import PatientHomeScreen from '../screens/patient/PatientHomeScreen';
import PatientCalendarScreen from '../screens/patient/PatientCalendarScreen';
import PatientProfileScreen from '../screens/patient/PatientProfileScreen';

const Tab = createBottomTabNavigator();

const PatientNavigator = () => {
  const theme = useTheme();

  return (
    <Tab.Navigator
      screenOptions={({route}) => ({
        tabBarIcon: ({focused, color, size}) => {
          let iconName;

          if (route.name === 'Home') {
            iconName = 'home';
          } else if (route.name === 'Calendar') {
            iconName = 'event';
          } else if (route.name === 'Profile') {
            iconName = 'person';
          }

          return <Icon name={iconName} size={size} color={color} />;
        },
        tabBarActiveTintColor: theme.colors.primary,
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
        component={PatientHomeScreen}
        options={{title: 'Home'}}
      />
      <Tab.Screen
        name="Calendar"
        component={PatientCalendarScreen}
        options={{title: 'Calendario'}}
      />
      <Tab.Screen
        name="Profile"
        component={PatientProfileScreen}
        options={{title: 'Profilo'}}
      />
    </Tab.Navigator>
  );
};

export default PatientNavigator;
