import React from 'react';
import {View} from 'react-native';
import {Text, Button, useTheme} from 'react-native-paper';
import ScreenTemplate from '../../components/ScreenTemplate';

const TherapistPatientsScreen = () => {
  const theme = useTheme();

  return (
    <ScreenTemplate title="Pazienti" subtitle="Gestisci i tuoi pazienti">
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
          Schermata Pazienti{'\n'}in sviluppo
        </Text>
        <Button mode="outlined" icon="account-plus">
          Aggiungi Nuovo Paziente
        </Button>
      </View>
    </ScreenTemplate>
  );
};

export default TherapistPatientsScreen;
