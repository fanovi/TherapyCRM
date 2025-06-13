import {createSlice} from '@reduxjs/toolkit';

const initialState = {
  networkError: null,
  isLoading: false,
  loadingMessage: null,
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
  },
});

export const {setNetworkError, clearNetworkError, setLoading, clearLoading} =
  uiSlice.actions;

export default uiSlice.reducer;
