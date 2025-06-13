import React from 'react';
import {View} from 'react-native';
import {Text, Button, useTheme} from 'react-native-paper';
import {useDispatch, useSelector} from 'react-redux';
import {loginService} from '../../services/loginService';
import ScreenTemplate from '../../components/ScreenTemplate';

const PatientProfileScreen = () => {
  const dispatch = useDispatch();
  const {user} = useSelector(state => state.auth);
  const theme = useTheme();

  const handleLogout = async () => {
    await loginService.logout(dispatch);
  };

  return (
    <ScreenTemplate
      title="Profilo"
      subtitle={`Ciao, ${user?.name || 'Paziente'}`}>
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

export default PatientProfileScreen;
