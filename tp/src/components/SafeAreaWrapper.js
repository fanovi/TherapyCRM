import React from 'react';
import {StyleSheet} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';

const SafeAreaWrapper = ({children, style, backgroundColor = '#FAFAFA'}) => {
  return (
    <SafeAreaView style={[styles.container, {backgroundColor}, style]}>
      {children}
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
});

export default SafeAreaWrapper;
