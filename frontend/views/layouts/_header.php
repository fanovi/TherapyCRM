<?php

use yii\helpers\Html;
use yii\helpers\Url;
?>
<!-- ===== Header Start ===== -->
<header
  x-data="{menuToggle: false}"
  class="sticky top-0 z-99999 flex w-full border-gray-200 bg-white lg:border-b dark:border-gray-800 dark:bg-gray-900">
  <div
    class="flex grow flex-col items-center justify-between lg:flex-row lg:px-6">
    <div
      class="flex w-full items-center justify-between gap-2 border-b border-gray-200 px-3 py-3 sm:gap-4 lg:justify-normal lg:border-b-0 lg:px-0 lg:py-4 dark:border-gray-800">
      <!-- Hamburger Toggle BTN -->
      <button
        :class="sidebarToggle ? 'lg:bg-transparent dark:lg:bg-transparent bg-gray-100 dark:bg-gray-800' : ''"
        class="z-99999 flex h-10 w-10 items-center justify-center rounded-lg border-gray-200 text-gray-500 lg:h-11 lg:w-11 lg:border dark:border-gray-800 dark:text-gray-400"
        @click.stop="sidebarToggle = !sidebarToggle">
        <svg
          class="hidden fill-current lg:block"
          width="16"
          height="12"
          viewBox="0 0 16 12"
          fill="none"
          xmlns="http://www.w3.org/2000/svg">
          <path
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M0.583252 1C0.583252 0.585788 0.919038 0.25 1.33325 0.25H14.6666C15.0808 0.25 15.4166 0.585786 15.4166 1C15.4166 1.41421 15.0808 1.75 14.6666 1.75L1.33325 1.75C0.919038 1.75 0.583252 1.41422 0.583252 1ZM0.583252 11C0.583252 10.5858 0.919038 10.25 1.33325 10.25L14.6666 10.25C15.0808 10.25 15.4166 10.5858 15.4166 11C15.4166 11.4142 15.0808 11.75 14.6666 11.75L1.33325 11.75C0.919038 11.75 0.583252 11.4142 0.583252 11ZM1.33325 5.25C0.919038 5.25 0.583252 5.58579 0.583252 6C0.583252 6.41421 0.919038 6.75 1.33325 6.75L7.99992 6.75C8.41413 6.75 8.74992 6.41421 8.74992 6C8.74992 5.58579 8.41413 5.25 7.99992 5.25L1.33325 5.25Z"
            fill="" />
        </svg>

        <svg
          :class="sidebarToggle ? 'hidden' : 'block lg:hidden'"
          class="fill-current lg:hidden"
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg">
          <path
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M3.25 6C3.25 5.58579 3.58579 5.25 4 5.25L20 5.25C20.4142 5.25 20.75 5.58579 20.75 6C20.75 6.41421 20.4142 6.75 20 6.75L4 6.75C3.58579 6.75 3.25 6.41422 3.25 6ZM3.25 18C3.25 17.5858 3.58579 17.25 4 17.25L20 17.25C20.4142 17.25 20.75 17.5858 20.75 18C20.75 18.4142 20.4142 18.75 20 18.75L4 18.75C3.58579 18.75 3.25 18.4142 3.25 18ZM4 11.25C3.58579 11.25 3.25 11.5858 3.25 12C3.25 12.4142 3.58579 12.75 4 12.75L12 12.75C12.4142 12.75 12.75 12.4142 12.75 12C12.75 11.5858 12.4142 11.25 12 11.25L4 11.25Z"
            fill="" />
        </svg>

        <!-- cross icon -->
        <svg
          :class="sidebarToggle ? 'block lg:hidden' : 'hidden'"
          class="fill-current"
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg">
          <path
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z"
            fill="" />
        </svg>
      </button>
      <!-- Hamburger Toggle BTN -->

      <a href="<?= Url::home(true) ?>" class="lg:hidden">
        <img class="dark:hidden h-10 w-10 sm:h-12 sm:w-12" src="<?= Url::to('@web/images/logo/cropped-flavicon-192x192.png') ?>" alt="Logo" />
        <img
          class="hidden dark:block h-10 w-10 sm:h-12 sm:w-12"
          src="<?= Url::to('@web/images/logo/logo-dark.svg') ?>"
          alt="Logo" />
      </a>

      <!-- Application nav menu button -->
      <button
        class="z-99999 flex h-10 w-10 items-center justify-center rounded-lg text-gray-700 hover:bg-gray-100 lg:hidden dark:text-gray-400 dark:hover:bg-gray-800"
        :class="menuToggle ? 'bg-gray-100 dark:bg-gray-800' : ''"
        @click.stop="menuToggle = !menuToggle">
        <svg
          class="fill-current"
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg">
          <path
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M5.99902 10.4951C6.82745 10.4951 7.49902 11.1667 7.49902 11.9951V12.0051C7.49902 12.8335 6.82745 13.5051 5.99902 13.5051C5.1706 13.5051 4.49902 12.8335 4.49902 12.0051V11.9951C4.49902 11.1667 5.1706 10.4951 5.99902 10.4951ZM17.999 10.4951C18.8275 10.4951 19.499 11.1667 19.499 11.9951V12.0051C19.499 12.8335 18.8275 13.5051 17.999 13.5051C17.1706 13.5051 16.499 12.8335 16.499 12.0051V11.9951C16.499 11.1667 17.1706 10.4951 17.999 10.4951ZM13.499 11.9951C13.499 11.1667 12.8275 10.4951 11.999 10.4951C11.1706 10.4951 10.499 11.1667 10.499 11.9951V12.0051C10.499 12.8335 11.1706 13.5051 11.999 13.5051C12.8275 13.5051 13.499 12.8335 13.499 12.0051V11.9951Z"
            fill="" />
        </svg>
      </button>
      <!-- Application nav menu button -->

      <div class="hidden lg:block">
        <form>
          <div class="relative" id="search-container">
            <span class="absolute top-1/2 left-4 -translate-y-1/2">
              <svg
                class="fill-gray-500 dark:fill-gray-400"
                width="20"
                height="20"
                viewBox="0 0 20 20"
                fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path
                  fill-rule="evenodd"
                  clip-rule="evenodd"
                  d="M3.04175 9.37363C3.04175 5.87693 5.87711 3.04199 9.37508 3.04199C12.8731 3.04199 15.7084 5.87693 15.7084 9.37363C15.7084 12.8703 12.8731 15.7053 9.37508 15.7053C5.87711 15.7053 3.04175 12.8703 3.04175 9.37363ZM9.37508 1.54199C5.04902 1.54199 1.54175 5.04817 1.54175 9.37363C1.54175 13.6991 5.04902 17.2053 9.37508 17.2053C11.2674 17.2053 13.003 16.5344 14.357 15.4176L17.177 18.238C17.4699 18.5309 17.9448 18.5309 18.2377 18.238C18.5306 17.9451 18.5306 17.4703 18.2377 17.1774L15.418 14.3573C16.5365 13.0033 17.2084 11.2669 17.2084 9.37363C17.2084 5.04817 13.7011 1.54199 9.37508 1.54199Z"
                  fill="" />
              </svg>
            </span>
            <input
              type="text"
              placeholder="Cerca utenti nel gestionale..."
              id="search-input"
              autocomplete="off"
              class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-200 bg-transparent py-2.5 pr-14 pl-12 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden xl:w-[430px] dark:border-gray-800 dark:bg-gray-900 dark:bg-white/[0.03] dark:text-white/90 dark:placeholder:text-white/30" />

            <button
              type="button"
              id="search-button"
              class="absolute top-1/2 right-2.5 inline-flex -translate-y-1/2 items-center gap-0.5 rounded-lg border border-gray-200 bg-gray-50 px-[7px] py-[4.5px] text-xs -tracking-[0.2px] text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
              <span> ⌘ </span>
              <span> K </span>
            </button>

            <!-- Loading spinner -->
            <div id="search-loading" class="absolute top-1/2 right-16 -translate-y-1/2 hidden">
              <svg class="animate-spin h-4 w-4 text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>

            <!-- Dropdown dei risultati -->
            <div
              id="search-results"
              class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-xl z-50 max-h-80 overflow-y-auto hidden">
              <div id="search-results-content"></div>
              <div id="search-no-results" class="p-4 text-center text-gray-500 dark:text-gray-400 hidden">
                <p>Nessun risultato trovato</p>
              </div>
              <div id="search-pagination" class="border-t border-gray-200 dark:border-gray-800 p-3 flex justify-between items-center hidden">
                <button type="button" id="search-prev" class="text-sm text-brand-600 hover:text-brand-700 disabled:text-gray-400 disabled:cursor-not-allowed">
                  ← Precedente
                </button>
                <span id="search-info" class="text-xs text-gray-500"></span>
                <button type="button" id="search-next" class="text-sm text-brand-600 hover:text-brand-700 disabled:text-gray-400 disabled:cursor-not-allowed">
                  Successivo →
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div
      :class="menuToggle ? 'flex' : 'hidden'"
      class="shadow-theme-md w-full items-center justify-between gap-4 px-5 py-4 lg:flex lg:justify-end lg:px-0 lg:shadow-none">
      <div class="2xsm:gap-3 flex items-center gap-2">
        <!-- Dark Mode Toggler -->
        <button
          class="hover:text-dark-900 relative flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
          @click.prevent="darkMode = !darkMode">
          <svg
            class="hidden dark:block"
            width="20"
            height="20"
            viewBox="0 0 20 20"
            fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M9.99998 1.5415C10.4142 1.5415 10.75 1.87729 10.75 2.2915V3.5415C10.75 3.95572 10.4142 4.2915 9.99998 4.2915C9.58577 4.2915 9.24998 3.95572 9.24998 3.5415V2.2915C9.24998 1.87729 9.58577 1.5415 9.99998 1.5415ZM10.0009 6.79327C8.22978 6.79327 6.79402 8.22904 6.79402 10.0001C6.79402 11.7712 8.22978 13.207 10.0009 13.207C11.772 13.207 13.2078 11.7712 13.2078 10.0001C13.2078 8.22904 11.772 6.79327 10.0009 6.79327ZM5.29402 10.0001C5.29402 7.40061 7.40135 5.29327 10.0009 5.29327C12.6004 5.29327 14.7078 7.40061 14.7078 10.0001C14.7078 12.5997 12.6004 14.707 10.0009 14.707C7.40135 14.707 5.29402 12.5997 5.29402 10.0001ZM15.9813 5.08035C16.2742 4.78746 16.2742 4.31258 15.9813 4.01969C15.6884 3.7268 15.2135 3.7268 14.9207 4.01969L14.0368 4.90357C13.7439 5.19647 13.7439 5.67134 14.0368 5.96423C14.3297 6.25713 14.8045 6.25713 15.0974 5.96423L15.9813 5.08035ZM18.4577 10.0001C18.4577 10.4143 18.1219 10.7501 17.7077 10.7501H16.4577C16.0435 10.7501 15.7077 10.4143 15.7077 10.0001C15.7077 9.58592 16.0435 9.25013 16.4577 9.25013H17.7077C18.1219 9.25013 18.4577 9.58592 18.4577 10.0001ZM14.9207 15.9806C15.2135 16.2735 15.6884 16.2735 15.9813 15.9806C16.2742 15.6877 16.2742 15.2128 15.9813 14.9199L15.0974 14.036C14.8045 13.7431 14.3297 13.7431 14.0368 14.036C13.7439 14.3289 13.7439 14.8038 14.0368 15.0967L14.9207 15.9806ZM9.99998 15.7088C10.4142 15.7088 10.75 16.0445 10.75 16.4588V17.7088C10.75 18.123 10.4142 18.4588 9.99998 18.4588C9.58577 18.4588 9.24998 18.123 9.24998 17.7088V16.4588C9.24998 16.0445 9.58577 15.7088 9.99998 15.7088ZM5.96356 15.0972C6.25646 14.8043 6.25646 14.3295 5.96356 14.0366C5.67067 13.7437 5.1958 13.7437 4.9029 14.0366L4.01902 14.9204C3.72613 15.2133 3.72613 15.6882 4.01902 15.9811C4.31191 16.274 4.78679 16.274 5.07968 15.9811L5.96356 15.0972ZM4.29224 10.0001C4.29224 10.4143 3.95645 10.7501 3.54224 10.7501H2.29224C1.87802 10.7501 1.54224 10.4143 1.54224 10.0001C1.54224 9.58592 1.87802 9.25013 2.29224 9.25013H3.54224C3.95645 9.25013 4.29224 9.58592 4.29224 10.0001ZM4.9029 5.9637C5.1958 6.25659 5.67067 6.25659 5.96356 5.9637C6.25646 5.6708 6.25646 5.19593 5.96356 4.90303L5.07968 4.01915C4.78679 3.72626 4.31191 3.72626 4.01902 4.01915C3.72613 4.31204 3.72613 4.78692 4.01902 5.07981L4.9029 5.9637Z"
              fill="currentColor" />
          </svg>
          <svg
            class="dark:hidden"
            width="20"
            height="20"
            viewBox="0 0 20 20"
            fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <path
              d="M17.4547 11.97L18.1799 12.1611C18.265 11.8383 18.1265 11.4982 17.8401 11.3266C17.5538 11.1551 17.1885 11.1934 16.944 11.4207L17.4547 11.97ZM8.0306 2.5459L8.57989 3.05657C8.80718 2.81209 8.84554 2.44682 8.67398 2.16046C8.50243 1.8741 8.16227 1.73559 7.83948 1.82066L8.0306 2.5459ZM12.9154 13.0035C9.64678 13.0035 6.99707 10.3538 6.99707 7.08524H5.49707C5.49707 11.1823 8.81835 14.5035 12.9154 14.5035V13.0035ZM16.944 11.4207C15.8869 12.4035 14.4721 13.0035 12.9154 13.0035V14.5035C14.8657 14.5035 16.6418 13.7499 17.9654 12.5193L16.944 11.4207ZM16.7295 11.7789C15.9437 14.7607 13.2277 16.9586 10.0003 16.9586V18.4586C13.9257 18.4586 17.2249 15.7853 18.1799 12.1611L16.7295 11.7789ZM10.0003 16.9586C6.15734 16.9586 3.04199 13.8433 3.04199 10.0003H1.54199C1.54199 14.6717 5.32892 18.4586 10.0003 18.4586V16.9586ZM3.04199 10.0003C3.04199 6.77289 5.23988 4.05695 8.22173 3.27114L7.83948 1.82066C4.21532 2.77574 1.54199 6.07486 1.54199 10.0003H3.04199ZM6.99707 7.08524C6.99707 5.52854 7.5971 4.11366 8.57989 3.05657L7.48132 2.03522C6.25073 3.35885 5.49707 5.13487 5.49707 7.08524H6.99707Z"
              fill="currentColor" />
          </svg>
        </button>
        <!-- Dark Mode Toggler -->

        <!-- Notification Menu Area -->
        <div
          class="relative"
          x-data="{ dropdownOpen: false, notifying: true }"
          @click.outside="dropdownOpen = false">
          <button
            class="hover:text-dark-900 relative flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
            @click.prevent="dropdownOpen = ! dropdownOpen; notifying = false">
            <span
                          id="notification-badge"
            class="absolute top-0.5 right-0 z-1 h-2 w-2 rounded-full bg-orange-400 hidden">
            <span
              class="absolute -z-1 inline-flex h-full w-full animate-ping rounded-full bg-orange-400 opacity-75"></span>
          </span>
            <svg
              class="fill-current"
              width="20"
              height="20"
              viewBox="0 0 20 20"
              fill="none"
              xmlns="http://www.w3.org/2000/svg">
              <path
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M10.75 2.29248C10.75 1.87827 10.4143 1.54248 10 1.54248C9.58583 1.54248 9.25004 1.87827 9.25004 2.29248V2.83613C6.08266 3.20733 3.62504 5.9004 3.62504 9.16748V14.4591H3.33337C2.91916 14.4591 2.58337 14.7949 2.58337 15.2091C2.58337 15.6234 2.91916 15.9591 3.33337 15.9591H4.37504H15.625H16.6667C17.0809 15.9591 17.4167 15.6234 17.4167 15.2091C17.4167 14.7949 17.0809 14.4591 16.6667 14.4591H16.375V9.16748C16.375 5.9004 13.9174 3.20733 10.75 2.83613V2.29248ZM14.875 14.4591V9.16748C14.875 6.47509 12.6924 4.29248 10 4.29248C7.30765 4.29248 5.12504 6.47509 5.12504 9.16748V14.4591H14.875ZM8.00004 17.7085C8.00004 18.1228 8.33583 18.4585 8.75004 18.4585H11.25C11.6643 18.4585 12 18.1228 12 17.7085C12 17.2943 11.6643 16.9585 11.25 16.9585H8.75004C8.33583 16.9585 8.00004 17.2943 8.00004 17.7085Z"
                fill="" />
            </svg>
          </button>

          <!-- Dropdown Start -->
          <div
            x-show="dropdownOpen"
            @click.stop
            class="shadow-theme-lg dark:bg-gray-dark absolute -right-[240px] mt-[17px] flex h-[480px] w-[350px] flex-col rounded-2xl border border-gray-200 bg-white p-3 sm:w-[361px] lg:right-0 dark:border-gray-800">
            <div
              class="mb-3 flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
              <h5
                class="text-lg font-semibold text-gray-800 dark:text-white/90">
                Comunicazioni 
                <span id="unread-count-header" class="hidden ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400">
                  <span id="unread-count-text">0</span>
                </span>
              </h5>

              <button
                @click="dropdownOpen = false"
                class="text-gray-500 dark:text-gray-400">
                <svg
                  class="fill-current"
                  width="24"
                  height="24"
                  viewBox="0 0 24 24"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg">
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z"
                    fill="" />
                </svg>
              </button>
            </div>

            <!-- Comunicazioni dinamiche via AJAX -->
            <div id="notifications-container">
              <div id="notifications-loading" class="flex items-center justify-center py-4">
                <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">Caricamento...</span>
              </div>
              
              <ul id="notifications-list" class="custom-scrollbar flex h-[350px] flex-col overflow-y-auto hidden">
                <!-- Le comunicazioni verranno caricate qui via AJAX -->
              </ul>
              
              <div id="notifications-empty" class="hidden py-6 text-center">
                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Nessuna comunicazione</p>
              </div>
            </div>

            <?= Html::a(
                'Vedi tutte le comunicazioni',
                ['communication/index'],
                [
                    'class' => 'text-theme-sm shadow-theme-xs mt-3 flex justify-center rounded-lg border border-gray-300 bg-white p-3 font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200'
                ]
            ) ?>
          </div>
          <!-- Dropdown End -->
        </div>
        <!-- Notification Menu Area -->
      </div>

      <!-- User Area -->
      <?php if (!Yii::$app->user->isGuest): ?>
      <?php
    $user = Yii::$app->user->identity;
    $profile = $user->profile;
    $fullName = $profile ? $profile->getFullName() : ($user->username ?? 'Utente');
    $firstName = $profile ? $profile->first_name : ($user->username ?? 'Utente');
    ?>
      <div
        class="relative"
        x-data="{ dropdownOpen: false }"
        @click.outside="dropdownOpen = false">
        <a
          class="flex items-center text-gray-700 dark:text-gray-400"
          href="#"
          @click.prevent="dropdownOpen = ! dropdownOpen">
          <span class="mr-3 h-11 w-11 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
            <?php if ($profile && !empty($profile->first_name)): ?>
              <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                <?= strtoupper(substr($profile->first_name, 0, 1) . substr($profile->last_name ?? '', 0, 1)) ?>
              </span>
            <?php else: ?>
              <svg class="h-6 w-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
              </svg>
            <?php endif; ?>
          </span>

          <span class="text-theme-sm mr-1 block font-medium"> <?= htmlspecialchars($firstName) ?> </span>

          <svg
            :class="dropdownOpen && 'rotate-180'"
            class="stroke-gray-500 dark:stroke-gray-400"
            width="18"
            height="20"
            viewBox="0 0 18 20"
            fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <path
              d="M4.3125 8.65625L9 13.3437L13.6875 8.65625"
              stroke=""
              stroke-width="1.5"
              stroke-linecap="round"
              stroke-linejoin="round" />
          </svg>
        </a>

        <!-- Dropdown Start -->
        <div
          x-show="dropdownOpen"
          class="shadow-theme-lg dark:bg-gray-dark absolute right-0 mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800">
          <div>
            <span
              class="text-theme-sm block font-medium text-gray-700 dark:text-gray-400">
              <?= htmlspecialchars($fullName) ?>
            </span>
            <span
              class="text-theme-xs mt-0.5 block text-gray-500 dark:text-gray-400">
              <?= htmlspecialchars($user->email) ?>
            </span>
          </div>

          <!-- Modifica Password Button -->
          <a
            href="<?= yii\helpers\Url::to(['/site/change-password']) ?>"
            class="group text-theme-sm mt-3 flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300 w-full">
            <svg
              class="fill-gray-500 group-hover:fill-gray-700 dark:group-hover:fill-gray-300"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg">
              <path
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M12 1C8.13 1 5 4.13 5 8C5 8.55 4.55 9 4 9H3C2.45 9 2 9.45 2 10V20C2 21.1 2.9 22 4 22H20C21.1 22 22 21.1 22 20V10C22 9.45 21.55 9 21 9H20C19.45 9 19 8.55 19 8C19 4.13 15.87 1 12 1ZM12 3C14.76 3 17 5.24 17 8C17 8.55 16.55 9 16 9H8C7.45 9 7 8.55 7 8C7 5.24 9.24 3 12 3ZM4 11H20V20H4V11ZM13 14C13 14.55 12.55 15 12 15C11.45 15 11 14.55 11 14V13C11 12.45 11.45 12 12 12C12.55 12 13 12.45 13 13V14Z"
                fill="" />
            </svg>

            Modifica Password
          </a>

         
          <?= yii\helpers\Html::beginForm(['/site/logout'], 'post', ['class' => 'w-full']) ?>
            <button
              type="submit"
              class="group text-theme-sm mt-3 flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300 w-full">
              <svg
                class="fill-gray-500 group-hover:fill-gray-700 dark:group-hover:fill-gray-300"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path
                  fill-rule="evenodd"
                  clip-rule="evenodd"
                  d="M15.1007 19.247C14.6865 19.247 14.3507 18.9112 14.3507 18.497L14.3507 14.245H12.8507V18.497C12.8507 19.7396 13.8581 20.747 15.1007 20.747H18.5007C19.7434 20.747 20.7507 19.7396 20.7507 18.497L20.7507 5.49609C20.7507 4.25345 19.7433 3.24609 18.5007 3.24609H15.1007C13.8581 3.24609 12.8507 4.25345 12.8507 5.49609V9.74501L14.3507 9.74501V5.49609C14.3507 5.08188 14.6865 4.74609 15.1007 4.74609L18.5007 4.74609C18.9149 4.74609 19.2507 5.08188 19.2507 5.49609L19.2507 18.497C19.2507 18.9112 18.9149 19.247 18.5007 19.247H15.1007ZM3.25073 11.9984C3.25073 12.2144 3.34204 12.4091 3.48817 12.546L8.09483 17.1556C8.38763 17.4485 8.86251 17.4487 9.15549 17.1559C9.44848 16.8631 9.44863 16.3882 9.15583 16.0952L5.81116 12.7484L16.0007 12.7484C16.4149 12.7484 16.7507 12.4127 16.7507 11.9984C16.7507 11.5842 16.4149 11.2484 16.0007 11.2484L5.81528 11.2484L9.15585 7.90554C9.44864 7.61255 9.44847 7.13767 9.15547 6.84488C8.86248 6.55209 8.3876 6.55226 8.09481 6.84525L3.52309 11.4202C3.35673 11.5577 3.25073 11.7657 3.25073 11.9984Z"
                  fill="" />
              </svg>

              Esci
            </button>
          <?= yii\helpers\Html::endForm() ?>
        </div>
        <!-- Dropdown End -->
      </div>
      <!-- User Area -->
      <?php else: ?>
      <!-- Guest User - Show Login Button -->
      <div class="flex items-center">
        <a href="<?= Url::to(['/site/login']) ?>" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-600 border border-transparent rounded-lg hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
          </svg>
          Accedi
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</header>
<!-- ===== Header End ===== -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const searchContainer = document.getElementById('search-container');
    const searchResults = document.getElementById('search-results');
    const searchResultsContent = document.getElementById('search-results-content');
    const searchNoResults = document.getElementById('search-no-results');
    const searchLoading = document.getElementById('search-loading');
    const searchPagination = document.getElementById('search-pagination');
    const searchPrev = document.getElementById('search-prev');
    const searchNext = document.getElementById('search-next');
    const searchInfo = document.getElementById('search-info');
    
    let currentQuery = '';
    let currentPage = 1;
    let totalPages = 1;
    let searchTimeout = null;
    let currentRequest = null;
    let suggestionsLoaded = false;

    // Funzione per nascondere i risultati
    function hideResults() {
        searchResults.classList.add('hidden');
        searchLoading.classList.add('hidden');
    }

    // Funzione per mostrare i risultati
    function showResults() {
        searchResults.classList.remove('hidden');
    }

    // Funzione per mostrare il loading
    function showLoading() {
        searchLoading.classList.remove('hidden');
        hideResultsContent();
    }

    // Funzione per nascondere il loading
    function hideLoading() {
        searchLoading.classList.add('hidden');
    }

    // Funzione per nascondere il contenuto dei risultati
    function hideResultsContent() {
        searchResultsContent.innerHTML = '';
        searchNoResults.classList.add('hidden');
        searchPagination.classList.add('hidden');
    }

    // Funzione per caricare i suggerimenti rapidi
    function loadQuickSuggestions() {
        if (suggestionsLoaded) {
            showResults();
            return;
        }

        showLoading();
        showResults();
        
        fetch(`<?= \yii\helpers\Url::to(['/search/quick-suggestions']) ?>?limit=5`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            hideLoading();
            
            if (data.success && data.data.length > 0) {
                displayQuickSuggestions(data.data);
                suggestionsLoaded = true;
            } else {
                hideResults();
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error loading suggestions:', error);
            hideResults();
        });
    }

    // Funzione per mostrare i suggerimenti rapidi
    function displayQuickSuggestions(suggestions) {
        hideResultsContent();
        
        // Aggiungi un header per i suggerimenti
        const headerHtml = `
            <div class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                <svg class="inline-block w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Pazienti recenti
            </div>
        `;
        
        const resultsHtml = headerHtml + suggestions.map(result => {
            const roleClass = getRoleClass(result.type);
            const avatarBg = getAvatarBgColor(result.type);
            
            return `
                <div class="search-result-item p-3 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0" 
                     data-url="${result.detail_url}" data-type="${result.type}">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full ${avatarBg} flex items-center justify-center">
                                <span class="text-sm font-medium text-white">${result.avatar_initials}</span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">${result.name}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${roleClass}">${result.role}</span>
                                ${result.last_visit ? `<span class="text-xs text-gray-400">• ${result.last_visit}</span>` : ''}
                            </div>
                            ${result.email ? `<p class="text-xs text-gray-400 truncate mt-1">${result.email}</p>` : ''}
                        </div>
                        <div class="flex-shrink-0">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        searchResultsContent.innerHTML = resultsHtml;
        
        // Aggiungi event listeners per i click
        document.querySelectorAll('.search-result-item').forEach(item => {
            item.addEventListener('click', function() {
                const url = this.dataset.url;
                if (url) {
                    window.location.href = url;
                }
            });
        });
    }

    // Funzione per eseguire la ricerca
    function performSearch(query, page = 1) {
        if (query.length < 2) {
            hideResults();
            return;
        }

        // Cancella la richiesta precedente se esiste
        if (currentRequest) {
            currentRequest.abort();
        }

        showLoading();
        showResults();

        // Crea l'URL per la ricerca
        const apiUrl = `<?= \yii\helpers\Url::to(['/search/user']) ?>?q=${encodeURIComponent(query)}&page=${page}&limit=10`;

        currentRequest = fetch(apiUrl, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            hideLoading();
            
            if (data.success && data.data.length > 0) {
                displayResults(data.data);
                updatePagination(data.pagination);
            } else {
                showNoResults();
            }
        })
        .catch(error => {
            hideLoading();
            if (error.name !== 'AbortError') {
                console.error('Search error:', error);
                showNoResults();
            }
        });
    }

    // Funzione per mostrare i risultati
    function displayResults(results) {
        hideResultsContent();
        
        const resultsHtml = results.map(result => {
            const roleClass = getRoleClass(result.type);
            const avatarBg = getAvatarBgColor(result.type);
            
            return `
                <div class="search-result-item p-3 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0" 
                     data-url="${result.detail_url}" data-type="${result.type}">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full ${avatarBg} flex items-center justify-center">
                                <span class="text-sm font-medium text-white">${result.avatar_initials}</span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">${result.name}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${roleClass}">${result.role}</span>
                            </p>
                            ${result.email ? `<p class="text-xs text-gray-400 truncate mt-1">${result.email}</p>` : ''}
                        </div>
                        <div class="flex-shrink-0">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        searchResultsContent.innerHTML = resultsHtml;
        
        // Aggiungi event listeners per i click
        document.querySelectorAll('.search-result-item').forEach(item => {
            item.addEventListener('click', function() {
                const url = this.dataset.url;
                if (url) {
                    window.location.href = url;
                }
            });
        });
    }

    // Funzione per mostrare nessun risultato
    function showNoResults() {
        hideResultsContent();
        searchNoResults.classList.remove('hidden');
    }

    // Funzione per aggiornare la paginazione
    function updatePagination(pagination) {
        if (pagination.pages > 1) {
            searchPagination.classList.remove('hidden');
            searchInfo.textContent = `Pagina ${pagination.page} di ${pagination.pages} (${pagination.total} risultati)`;
            
            currentPage = pagination.page;
            totalPages = pagination.pages;
            
            searchPrev.disabled = currentPage === 1;
            searchNext.disabled = currentPage === totalPages;
            
            if (searchPrev.disabled) {
                searchPrev.classList.add('disabled:text-gray-400', 'disabled:cursor-not-allowed');
            } else {
                searchPrev.classList.remove('disabled:text-gray-400', 'disabled:cursor-not-allowed');
            }
            
            if (searchNext.disabled) {
                searchNext.classList.add('disabled:text-gray-400', 'disabled:cursor-not-allowed');
            } else {
                searchNext.classList.remove('disabled:text-gray-400', 'disabled:cursor-not-allowed');
            }
        } else {
            searchPagination.classList.add('hidden');
        }
    }

    // Funzione per ottenere la classe CSS del ruolo
    function getRoleClass(type) {
        switch(type) {
            case 'therapist':
                return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
            case 'patient':
                return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            case 'patient_account':
                return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
            case 'admin':
                return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            case 'manager':
                return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
            case 'coordinator':
                return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
        }
    }

    // Funzione per ottenere il colore di sfondo dell'avatar
    function getAvatarBgColor(type) {
        switch(type) {
            case 'therapist':
                return 'bg-blue-500';
            case 'patient':
                return 'bg-green-500';
            case 'patient_account':
                return 'bg-purple-500';
            case 'admin':
                return 'bg-red-500';
            case 'manager':
                return 'bg-orange-500';
            case 'coordinator':
                return 'bg-yellow-500';
            default:
                return 'bg-gray-500';
        }
    }

    // Event listener per l'input di ricerca
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        currentQuery = query;
        currentPage = 1;
        suggestionsLoaded = false; // Reset suggestions flag
        
        // Se l'utente cancella tutto il testo, mostra di nuovo i suggerimenti
        if (query.length === 0) {
            loadQuickSuggestions();
            return;
        }
        
        // Cancella il timeout precedente
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Imposta un nuovo timeout per evitare troppe richieste
        searchTimeout = setTimeout(() => {
            performSearch(query, 1);
        }, 300);
    });

    // Event listener per il focus sull'input
    searchInput.addEventListener('focus', function() {
        if (currentQuery && currentQuery.length >= 2) {
            showResults();
        } else if (!currentQuery) {
            // Se non c'è query, mostra i suggerimenti rapidi
            loadQuickSuggestions();
        }
    });

    // Event listener per nascondere i risultati quando si clicca fuori
    document.addEventListener('click', function(event) {
        if (!searchContainer.contains(event.target)) {
            hideResults();
        }
    });

    // Event listeners per la paginazione
    searchPrev.addEventListener('click', function() {
        if (currentPage > 1) {
            currentPage--;
            performSearch(currentQuery, currentPage);
        }
    });

    searchNext.addEventListener('click', function() {
        if (currentPage < totalPages) {
            currentPage++;
            performSearch(currentQuery, currentPage);
        }
    });

    // Supporto per scorciatoia da tastiera (Cmd+K o Ctrl+K)
    document.addEventListener('keydown', function(event) {
        if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
            event.preventDefault();
            searchInput.focus();
        }
        
        // Supporto per navigazione con frecce nei risultati
        if (searchResults.classList.contains('hidden') === false) {
            if (event.key === 'Escape') {
                hideResults();
                searchInput.blur();
            }
        }
    });
});

// ===== Sistema Comunicazioni Dropdown =====
class NotificationSystem {
    constructor() {
        this.container = document.getElementById('notifications-container');
        this.loadingElement = document.getElementById('notifications-loading');
        this.listElement = document.getElementById('notifications-list');
        this.emptyElement = document.getElementById('notifications-empty');
        this.badge = document.getElementById('notification-badge');
        this.countHeader = document.getElementById('unread-count-header');
        this.countText = document.getElementById('unread-count-text');
        
        this.isLoaded = false;
        this.unreadCount = 0;
        
        this.init();
    }
    
    init() {
        // Carica comunicazioni quando si apre il dropdown
        const notificationButton = document.querySelector('[x-data] button');
        const dropdown = notificationButton?.closest('[x-data]');
        
        if (dropdown) {
            // Usa Alpine.js per intercettare l'apertura del dropdown
            dropdown.addEventListener('click', () => {
                setTimeout(() => {
                    if (!this.isLoaded) {
                        this.loadNotifications();
                    }
                }, 100);
            });
        }
        
        // Caricamento iniziale per il badge
        this.loadUnreadCount();
    }
    
    async loadNotifications() {
        if (this.isLoaded) return;
        
        try {
            this.showLoading();
            
            const response = await fetch('<?= \yii\helpers\Url::to(['/communication/header-preview']) ?>', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.renderNotifications(data.data.notifications);
                this.updateUnreadCount(data.data.unread_count);
                this.isLoaded = true;
            } else {
                this.showEmpty();
            }
            
        } catch (error) {
            console.error('Errore caricamento comunicazioni:', error);
            this.showEmpty();
        } finally {
            this.hideLoading();
        }
    }
    
    async loadUnreadCount() {
        try {
            const response = await fetch('<?= \yii\helpers\Url::to(['/communication/header-preview']) ?>', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.updateUnreadCount(data.data.unread_count);
            }
            
        } catch (error) {
            console.error('Errore caricamento conteggio:', error);
        }
    }
    
    renderNotifications(notifications) {
        if (!notifications || notifications.length === 0) {
            this.showEmpty();
            return;
        }
        
        const html = notifications.map(notification => this.createNotificationHtml(notification)).join('');
        this.listElement.innerHTML = html;
        this.listElement.classList.remove('hidden');
        this.emptyElement.classList.add('hidden');
    }
    
    createNotificationHtml(notification) {
        const typeColors = {
            'info': 'blue',
            'reminder': 'amber', 
            'deadline': 'red',
            'mandatory_read': 'red',
            'internal_communication': 'green'
        };
        
        const color = typeColors[notification.notification_type] || 'gray';
        const isUnread = !notification.is_read;
        
        return `
            <li>
                <a href="${notification.view_url}" 
                   class="flex gap-3 rounded-lg border-b border-gray-100 p-3 px-4.5 py-3 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-white/5 ${isUnread ? 'bg-blue-50 dark:bg-blue-900/10' : ''}">
                    <span class="relative z-1 block h-10 w-full max-w-10 rounded-full">
                        <div class="w-10 h-10 rounded-full bg-${color}-100 dark:bg-${color}-900/20 flex items-center justify-center">
                            ${this.getTypeIcon(notification.notification_type, color)}
                        </div>
                        ${isUnread ? '<span class="bg-orange-400 absolute right-0 bottom-0 z-10 h-2.5 w-full max-w-2.5 rounded-full border-[1.5px] border-white dark:border-gray-900"></span>' : ''}
                    </span>
                    
                    <span class="block flex-1">
                        <span class="text-theme-sm mb-1.5 block text-gray-500 dark:text-gray-400">
                            <span class="font-medium text-gray-800 dark:text-white/90">${this.escapeHtml(notification.title)}</span>
                            ${notification.message_preview ? '<br>' + this.escapeHtml(notification.message_preview) : ''}
                        </span>
                        
                        <span class="text-theme-xs flex items-center gap-2 text-gray-500 dark:text-gray-400">
                            <span>${notification.type_label}</span>
                            <span class="h-1 w-1 rounded-full bg-gray-400"></span>
                            <span>${notification.time_ago}</span>
                        </span>
                    </span>
                </a>
            </li>
        `;
    }
    
    getTypeIcon(type, color) {
        const icons = {
            'info': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
            'reminder': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5V8h-5l5-5 5 5h-5v9z"></path>',
            'deadline': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
            'mandatory_read': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>',
            'internal_communication': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>'
        };
        
        const iconPath = icons[type] || icons['info'];
        
        return `
            <svg class="w-5 h-5 text-${color}-600 dark:text-${color}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                ${iconPath}
            </svg>
        `;
    }
    
    updateUnreadCount(count) {
        this.unreadCount = count;
        
        if (count > 0) {
            this.badge?.classList.remove('hidden');
            this.countHeader?.classList.remove('hidden');
            this.countText.textContent = count;
        } else {
            this.badge?.classList.add('hidden');
            this.countHeader?.classList.add('hidden');
        }
    }
    
    showLoading() {
        this.loadingElement?.classList.remove('hidden');
        this.listElement?.classList.add('hidden');
        this.emptyElement?.classList.add('hidden');
    }
    
    hideLoading() {
        this.loadingElement?.classList.add('hidden');
    }
    
    showEmpty() {
        this.listElement?.classList.add('hidden');
        this.emptyElement?.classList.remove('hidden');
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Metodo pubblico per ricaricare le comunicazioni
    refresh() {
        this.isLoaded = false;
        this.loadNotifications();
        this.loadUnreadCount();
    }
}

// Inizializza il sistema quando il DOM è pronto
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.notificationSystem === 'undefined') {
        window.notificationSystem = new NotificationSystem();
    }
});
</script>