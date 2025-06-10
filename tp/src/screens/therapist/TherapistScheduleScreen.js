import React from 'react';
import {View} from 'react-native';
import {Text, Button, useTheme} from 'react-native-paper';
import ScreenTemplate from '../../components/ScreenTemplate';

const TherapistScheduleScreen = () => {
  const theme = useTheme();

  return (
    <ScreenTemplate title="Agenda" subtitle="La tua pianificazione">
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
          Schermata Agenda{'\n'}in sviluppo
        </Text>
        <Button mode="outlined" icon="calendar-plus">
          Aggiungi Appuntamento
        </Button>
      </View>
    </ScreenTemplate>
  );
};

export default TherapistScheduleScreen;
