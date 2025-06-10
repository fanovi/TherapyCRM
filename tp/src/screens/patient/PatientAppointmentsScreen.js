import React from 'react';
import {View} from 'react-native';
import {Text, Button, useTheme} from 'react-native-paper';
import ScreenTemplate from '../../components/ScreenTemplate';

const PatientAppointmentsScreen = () => {
  const theme = useTheme();

  return (
    <ScreenTemplate title="Appuntamenti" subtitle="I tuoi appuntamenti">
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
          Schermata Appuntamenti{'\n'}in sviluppo
        </Text>
        <Button mode="outlined" icon="calendar">
          Prenota Nuovo Appuntamento
        </Button>
      </View>
    </ScreenTemplate>
  );
};

export default PatientAppointmentsScreen;
