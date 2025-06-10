import React from 'react';
import {View, StyleSheet, ScrollView, StatusBar} from 'react-native';
import {Text, IconButton, useTheme} from 'react-native-paper';

const ScreenTemplate = ({
  children,
  title,
  subtitle,
  headerRight,
  scrollable = true,
  showHeader = true,
  useBackgroundColor = true,
  useHeaderBackground = true,
}) => {
  const theme = useTheme();
  const ContentComponent = scrollable ? ScrollView : View;

  const backgroundColor = useBackgroundColor
    ? theme.colors.background
    : 'transparent';
  const headerBackgroundColor = useHeaderBackground
    ? theme.colors.surface
    : 'transparent';

  return (
    <View style={[styles.container, {backgroundColor}]}>
      <StatusBar
        barStyle="dark-content"
        backgroundColor={backgroundColor}
        translucent={false}
      />

      {showHeader && (
        <View
          style={[
            styles.header,
            {
              backgroundColor: headerBackgroundColor,
              borderBottomColor: theme.colors.outline,
            },
          ]}>
          <View style={styles.headerContent}>
            <View style={styles.headerLeft}>
              {title && (
                <Text style={[styles.title, {color: theme.colors.onSurface}]}>
                  {title}
                </Text>
              )}
              {subtitle && (
                <Text
                  style={[
                    styles.subtitle,
                    {color: theme.colors.onSurfaceVariant},
                  ]}>
                  {subtitle}
                </Text>
              )}
            </View>
            <View style={styles.headerRight}>{headerRight}</View>
          </View>
        </View>
      )}

      <ContentComponent
        style={styles.content}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={scrollable ? styles.scrollContent : undefined}>
        {children}
      </ContentComponent>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  header: {
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderBottomWidth: 1,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: {width: 0, height: 1},
    shadowOpacity: 0.1,
    shadowRadius: 2,
  },
  headerContent: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  headerLeft: {
    flex: 1,
  },
  headerRight: {
    marginLeft: 16,
  },
  title: {
    fontSize: 24,
    fontWeight: '600',
    marginBottom: 4,
  },
  subtitle: {
    fontSize: 14,
    fontWeight: '400',
  },
  content: {
    flex: 1,
  },
  scrollContent: {
    flexGrow: 1,
    paddingBottom: 20,
  },
});

export default ScreenTemplate;
