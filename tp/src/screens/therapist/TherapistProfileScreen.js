import React from 'react';
import {View} from 'react-native';
import {Text, Button, useTheme} from 'react-native-paper';
import {useDispatch, useSelector} from 'react-redux';
import {logoutUser} from '../../store/authSlice';
import ScreenTemplate from '../../components/ScreenTemplate';

const TherapistProfileScreen = () => {
  const dispatch = useDispatch();
  const {user} = useSelector(state => state.auth);
  const theme = useTheme();

  const handleLogout = () => {
    dispatch(logoutUser());
  };

  return (
    <ScreenTemplate
      title="Profilo"
      subtitle={`Dr. ${user?.name || 'Terapista'}`}>
      <View
        style={{
          flex: 1,
          justifyContent: 'center',
          alignItems: 'center',
          padding: 20,
        }}>
        <Text
          style={{
            fontSize: 18,
            textAlign: 'center',
            marginBottom: 20,
            color: theme.colors.onSurfaceVariant,
          }}>
          Schermata Profilo{'\n'}in sviluppo
        </Text>
        <Button
          mode="contained"
          onPress={handleLogout}
          buttonColor={theme.colors.error}
          icon="logout">
          Esci
        </Button>
      </View>
    </ScreenTemplate>
  );
};

export default TherapistProfileScreen;
