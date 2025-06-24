import {createSlice} from '@reduxjs/toolkit';

const initialState = {
  networkError: null,
  isLoading: false,
  loadingMessage: null,
  // Stato notifiche bloccanti
  blockingNotifications: [],
  isAppBlocked: false,
  selectedNotification: null,
  isNotificationModalOpen: false,
};

const uiSlice = createSlice({
  name: 'ui',
  initialState,
  reducers: {
    setNetworkError: (state, action) => {
      state.networkError = action.payload;
    },
    clearNetworkError: state => {
      state.networkError = null;
    },
    setLoading: (state, action) => {
      state.isLoading = action.payload.isLoading;
      state.loadingMessage = action.payload.message || null;
    },
    clearLoading: state => {
      state.isLoading = false;
      state.loadingMessage = null;
    },
    // Actions per notifiche bloccanti
    setBlockingNotifications: (state, action) => {
      state.blockingNotifications = action.payload;
      state.isAppBlocked = action.payload.length > 0;
    },
    removeBlockingNotification: (state, action) => {
      state.blockingNotifications = state.blockingNotifications.filter(
        notification => notification.id !== action.payload,
      );
      state.isAppBlocked = state.blockingNotifications.length > 0;
    },
    clearBlockingNotifications: state => {
      state.blockingNotifications = [];
      state.isAppBlocked = false;
    },
    openNotificationModal: (state, action) => {
      state.selectedNotification = action.payload;
      state.isNotificationModalOpen = true;
    },
    closeNotificationModal: state => {
      state.selectedNotification = null;
      state.isNotificationModalOpen = false;
    },
  },
});

export const {
  setNetworkError,
  clearNetworkError,
  setLoading,
  clearLoading,
  setBlockingNotifications,
  removeBlockingNotification,
  clearBlockingNotifications,
  openNotificationModal,
  closeNotificationModal,
} = uiSlice.actions;

export default uiSlice.reducer;
