import {MD3LightTheme, MD3DarkTheme} from 'react-native-paper';

const lightTheme = {
  ...MD3LightTheme,
  colors: {
    ...MD3LightTheme.colors,
    // Colori personalizzati per l'app sanitaria
    primary: '#2196F3', // Blu primario
    primaryContainer: '#E3F2FD',
    secondary: '#00695C', // Verde terapista
    secondaryContainer: '#E0F2F1',
    tertiary: '#FF7043', // Arancione per reset password
    tertiaryContainer: '#FFF3E0',
    error: '#F44336',
    errorContainer: '#FFEBEE',
    background: '#FAFAFA',
    surface: '#FFFFFF',
    surfaceVariant: '#F5F5F5',
    onSurface: '#212121',
    onSurfaceVariant: '#757575',
    outline: '#E0E0E0',
    outlineVariant: '#F0F0F0',
    // Colori custom per ruoli
    patient: '#2196F3',
    therapist: '#00695C',
    success: '#4CAF50',
    warning: '#FF9800',
  },
};

const darkTheme = {
  ...MD3DarkTheme,
  colors: {
    ...MD3DarkTheme.colors,
    // Versione dark del tema (opzionale)
    primary: '#64B5F6',
    secondary: '#4DB6AC',
    tertiary: '#FFB74D',
    background: '#121212',
    surface: '#1E1E1E',
    patient: '#64B5F6',
    therapist: '#4DB6AC',
    success: '#66BB6A',
    warning: '#FFB74D',
  },
};

export {lightTheme, darkTheme};
export default lightTheme;
