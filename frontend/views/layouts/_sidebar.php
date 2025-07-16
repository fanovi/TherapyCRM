<?php

use yii\helpers\Url;
?>
<!-- ===== Sidebar Start ===== -->
<aside
    :class="sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'"
    class="sidebar fixed left-0 top-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-hidden border-r border-gray-200 bg-white px-5 dark:border-gray-800 dark:bg-black lg:static lg:translate-x-0">
    <!-- SIDEBAR HEADER -->
    <div
        :class="sidebarToggle ? 'justify-center' : 'justify-between'"
        class="flex items-center gap-2 pt-8 sidebar-header pb-7">
        <a href="<?= Url::home(true) ?>">
            <span class="logo" :class="sidebarToggle ? 'hidden' : ''">
                <img class="dark:hidden" src="<?= Url::to('@web/images/logo/logo.svg') ?>" alt="Logo" />
                <img
                    class="hidden dark:block"
                    src="<?= Url::to('@web/images/logo/logo-dark.svg') ?>"
                    alt="Logo" />
            </span>

            <img
                class="logo-icon"
                :class="sidebarToggle ? 'lg:block' : 'hidden'"
                src="<?= Url::to('@web/images/logo/logo-icon.svg') ?>"
                alt="Logo" />
        </a>
    </div>
    <!-- SIDEBAR HEADER -->

    <div
        class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">
        <!-- Sidebar Menu -->
        <nav x-data="{selected: $persist('Dashboard')}">
            <!-- Menu Group -->
            <div>
                <h3 class="mb-4 text-xs uppercase leading-[20px] text-gray-400">
                    <span
                        class="menu-group-title"
                        :class="sidebarToggle ? 'lg:hidden' : ''">
                        MENU
                    </span>

                    <svg
                        :class="sidebarToggle ? 'lg:block hidden' : 'hidden'"
                        class="mx-auto fill-current menu-group-icon"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            fill-rule="evenodd"
                            clip-rule="evenodd"
                            d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                            fill="" />
                    </svg>
                </h3>

                <ul class="flex flex-col gap-4 mb-6">
                    <!-- Menu Item Dashboard -->
                    <li>
                        <a
                            href="#"
                            @click.prevent="selected = (selected === 'Dashboard' ? '':'Dashboard')"
                            class="menu-item group"
                            :class=" (selected === 'Dashboard') || (page === 'ecommerce' || page === 'analytics' || page === 'marketing' || page === 'crm' || page === 'stocks') ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'Dashboard') || (page === 'ecommerce' || page === 'analytics' || page === 'marketing' || page === 'crm' || page === 'stocks') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M5.5 3.25C4.25736 3.25 3.25 4.25736 3.25 5.5V8.99998C3.25 10.2426 4.25736 11.25 5.5 11.25H9C10.2426 11.25 11.25 10.2426 11.25 8.99998V5.5C11.25 4.25736 10.2426 3.25 9 3.25H5.5ZM4.75 5.5C4.75 5.08579 5.08579 4.75 5.5 4.75H9C9.41421 4.75 9.75 5.08579 9.75 5.5V8.99998C9.75 9.41419 9.41421 9.74998 9 9.74998H5.5C5.08579 9.74998 4.75 9.41419 4.75 8.99998V5.5ZM5.5 12.75C4.25736 12.75 3.25 13.7574 3.25 15V18.5C3.25 19.7426 4.25736 20.75 5.5 20.75H9C10.2426 20.75 11.25 19.7427 11.25 18.5V15C11.25 13.7574 10.2426 12.75 9 12.75H5.5ZM4.75 15C4.75 14.5858 5.08579 14.25 5.5 14.25H9C9.41421 14.25 9.75 14.5858 9.75 15V18.5C9.75 18.9142 9.41421 19.25 9 19.25H5.5C5.08579 19.25 4.75 18.9142 4.75 18.5V15ZM12.75 5.5C12.75 4.25736 13.7574 3.25 15 3.25H18.5C19.7426 3.25 20.75 4.25736 20.75 5.5V8.99998C20.75 10.2426 19.7426 11.25 18.5 11.25H15C13.7574 11.25 12.75 10.2426 12.75 8.99998V5.5ZM15 4.75C14.5858 4.75 14.25 5.08579 14.25 5.5V8.99998C14.25 9.41419 14.5858 9.74998 15 9.74998H18.5C18.9142 9.74998 19.25 9.41419 19.25 8.99998V5.5C19.25 5.08579 18.9142 4.75 18.5 4.75H15ZM15 12.75C13.7574 12.75 12.75 13.7574 12.75 15V18.5C12.75 19.7426 13.7574 20.75 15 20.75H18.5C19.7426 20.75 20.75 19.7427 20.75 18.5V15C20.75 13.7574 19.7426 12.75 18.5 12.75H15ZM14.25 15C14.25 14.5858 14.5858 14.25 15 14.25H18.5C18.9142 14.25 19.25 14.5858 19.25 15V18.5C19.25 18.9142 18.9142 19.25 18.5 19.25H15C14.5858 19.25 14.25 18.9142 14.25 18.5V15Z"
                                    fill="" />
                            </svg>

                            <span
                                class="menu-item-text"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                Dashboard
                            </span>

                            
                        </a>

                        <!-- Dropdown Menu Start -->
                        <div
                            class="overflow-hidden transform translate"
                            :class="(selected === 'Dashboard') ? 'block' :'hidden'">
                           
                        </div>
                        <!-- Dropdown Menu End -->
                    </li>
                    <!-- Menu Item Dashboard -->

                    <!-- Menu Item Calendar -->
                    <li>
                        <a
                            href="calendar.html"
                            @click="selected = (selected === 'Calendar' ? '':'Calendar')"
                            class="menu-item group"
                            :class=" (selected === 'Calendar') && (page === 'calendar') ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'Calendar') && (page === 'calendar') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M8 2C8.41421 2 8.75 2.33579 8.75 2.75V3.75H15.25V2.75C15.25 2.33579 15.5858 2 16 2C16.4142 2 16.75 2.33579 16.75 2.75V3.75H18.5C19.7426 3.75 20.75 4.75736 20.75 6V9V19C20.75 20.2426 19.7426 21.25 18.5 21.25H5.5C4.25736 21.25 3.25 20.2426 3.25 19V9V6C3.25 4.75736 4.25736 3.75 5.5 3.75H7.25V2.75C7.25 2.33579 7.58579 2 8 2ZM8 5.25H5.5C5.08579 5.25 4.75 5.58579 4.75 6V8.25H19.25V6C19.25 5.58579 18.9142 5.25 18.5 5.25H16H8ZM19.25 9.75H4.75V19C4.75 19.4142 5.08579 19.75 5.5 19.75H18.5C18.9142 19.75 19.25 19.4142 19.25 19V9.75Z"
                                    fill="" />
                            </svg>

                            <span
                                class="menu-item-text"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                Calendar
                            </span>
                        </a>
                    </li>
                    <!-- Menu Item Calendar -->

                    <!-- Menu Item Statistics -->
                    <?php if (Yii::$app->user->can('view_statistics')): ?>
                    <li>
                        <a
                            href="<?= Url::to(['statistics/index']) ?>"
                            @click="selected = (selected === 'Statistics' ? '':'Statistics')"
                            class="menu-item group"
                            :class=" (selected === 'Statistics') && (page === 'statistics') ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'Statistics') && (page === 'statistics') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M3 6C3 4.34315 4.34315 3 6 3H18C19.6569 3 21 4.34315 21 6V18C21 19.6569 19.6569 21 18 21H6C4.34315 21 3 19.6569 3 18V6ZM6 5C5.44772 5 5 5.44772 5 6V18C5 18.5523 5.44772 19 6 19H18C18.5523 19 19 18.5523 19 18V6C19 5.44772 18.5523 5 18 5H6ZM7 8C7 7.44772 7.44772 7 8 7C8.55228 7 9 7.44772 9 8V16C9 16.5523 8.55228 17 8 17C7.44772 17 7 16.5523 7 16V8ZM11 10C11 9.44772 11.4477 9 12 9C12.5523 9 13 9.44772 13 10V16C13 16.5523 12.5523 17 12 17C11.4477 17 11 16.5523 11 16V10ZM15 12C15 11.4477 15.4477 11 16 11C16.5523 11 17 11.4477 17 12V16C17 16.5523 16.5523 17 16 17C15.4477 17 15 16.5523 15 16V12Z"
                                    fill="" />
                            </svg>

                            <span
                                class="menu-item-text"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                Statistiche
                            </span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <!-- Menu Item Statistics -->

                    <!-- Menu Item Administrators -->
                    <?php if (Yii::$app->user->can('create_admin')): ?>
                    <li>
                        <a
                            href="#"
                            @click.prevent="selected = (selected === 'Administrators' ? '':'Administrators')"
                            class="menu-item group"
                            :class="(selected === 'Administratcleaors') ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'Administrators') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M5 3.5C3.61929 3.5 2.5 4.61929 2.5 6V18C2.5 19.3807 3.61929 20.5 5 20.5H19C20.3807 20.5 21.5 19.3807 21.5 18V6C21.5 4.61929 20.3807 3.5 19 3.5H5ZM4 6C4 5.44772 4.44772 5 5 5H19C19.5523 5 20 5.44772 20 6V8H4V6ZM4 18V9.5H20V18C20 18.5523 19.5523 19 19 19H5C4.44772 19 4 18.5523 4 18ZM6.5 12.75C6.5 12.3358 6.83579 12 7.25 12H11.25C11.6642 12 12 12.3358 12 12.75C12 13.1642 11.6642 13.5 11.25 13.5H7.25C6.83579 13.5 6.5 13.1642 6.5 12.75ZM15.5 14.5C16.0523 14.5 16.5 14.0523 16.5 13.5C16.5 12.9477 16.0523 12.5 15.5 12.5C14.9477 12.5 14.5 12.9477 14.5 13.5C14.5 14.0523 14.9477 14.5 15.5 14.5ZM18 13.5C18 14.0523 17.5523 14.5 17 14.5C16.4477 14.5 16 14.0523 16 13.5C16 12.9477 16.4477 12.5 17 12.5C17.5523 12.5 18 12.9477 18 13.5Z"
                                    fill="" />
                            </svg>

                            <span
                                class="menu-item-text"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                Amministratori
                            </span>

                            <svg
                                class="menu-item-arrow"
                                :class="[(selected === 'Administrators') ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'lg:hidden' : '' ]"
                                width="20"
                                height="20"
                                viewBox="0 0 20 20"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585"
                                    stroke=""
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </a>

                        <!-- Dropdown Menu Start -->
                        <div
                            class="overflow-hidden transform translate"
                            :class="(selected === 'Administrators') ? 'block' :'hidden'">
                            <ul
                                :class="sidebarToggle ? 'lg:hidden' : 'flex'"
                                class="flex flex-col gap-1 mt-2 menu-dropdown pl-9">
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/user/administrators']) ?>"
                                        class="menu-dropdown-item group">
                                        Visualizza Amministratori
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/user/create-administrator']) ?>"
                                        class="menu-dropdown-item group">
                                        Nuovo Amministratore
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <!-- Dropdown Menu End -->
                    </li>
                    <?php endif; ?>
                    <!-- Menu Item Administrators -->

                    <!-- Menu Item Coordinators -->
                    <?php if (Yii::$app->user->can('create_coordinator')): ?>
                    <li>
                        <a
                            href="#"
                            @click.prevent="selected = (selected === 'Coordinators' ? '':'Coordinators')"
                            class="menu-item group"
                            :class="(selected === 'Coordinators') ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'Coordinators') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M12 2C13.1046 2 14 2.89543 14 4C14 5.10457 13.1046 6 12 6C10.8954 6 10 5.10457 10 4C10 2.89543 10.8954 2 12 2ZM12 3.5C11.7239 3.5 11.5 3.72386 11.5 4C11.5 4.27614 11.7239 4.5 12 4.5C12.2761 4.5 12.5 4.27614 12.5 4C12.5 3.72386 12.2761 3.5 12 3.5ZM7.5 8.5C8.32843 8.5 9 9.17157 9 10C9 10.8284 8.32843 11.5 7.5 11.5C6.67157 11.5 6 10.8284 6 10C6 9.17157 6.67157 8.5 7.5 8.5ZM16.5 8.5C17.3284 8.5 18 9.17157 18 10C18 10.8284 17.3284 11.5 16.5 11.5C15.6716 11.5 15 10.8284 15 10C15 9.17157 15.6716 8.5 16.5 8.5ZM5.25 14C4.00736 14 3 15.0074 3 16.25V19.75C3 20.9926 4.00736 22 5.25 22H18.75C19.9926 22 21 20.9926 21 19.75V16.25C21 15.0074 19.9926 14 18.75 14H5.25ZM4.5 16.25C4.5 15.8358 4.83579 15.5 5.25 15.5H18.75C19.1642 15.5 19.5 15.8358 19.5 16.25V19.75C19.5 20.1642 19.1642 20.5 18.75 20.5H5.25C4.83579 20.5 4.5 20.1642 4.5 19.75V16.25Z"
                                    fill="" />
                            </svg>

                            <span
                                class="menu-item-text"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                Coordinatori
                            </span>

                            <svg
                                class="menu-item-arrow"
                                :class="[(selected === 'Coordinators') ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'lg:hidden' : '' ]"
                                width="20"
                                height="20"
                                viewBox="0 0 20 20"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585"
                                    stroke=""
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </a>

                        <!-- Dropdown Menu Start -->
                        <div
                            class="overflow-hidden transform translate"
                            :class="(selected === 'Coordinators') ? 'block' :'hidden'">
                            <ul
                                :class="sidebarToggle ? 'lg:hidden' : 'flex'"
                                class="flex flex-col gap-1 mt-2 menu-dropdown pl-9">
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/user/coordinators']) ?>"
                                        class="menu-dropdown-item group">
                                        Visualizza Coordinatori
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/user/create-coordinator']) ?>"
                                        class="menu-dropdown-item group">
                                        Nuovo Coordinatore
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <!-- Dropdown Menu End -->
                    </li>
                    <?php endif; ?>
                    <!-- Menu Item Coordinators -->

                    <!-- Menu Item Coordinator Groups -->
                    <?php if (Yii::$app->user->can('view_coordinator_group')): ?>
                    <li>
                        <a
                            href="#"
                            @click.prevent="selected = (selected === 'CoordinatorGroups' ? '':'CoordinatorGroups')"
                            class="menu-item group"
                            :class="(selected === 'CoordinatorGroups') ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'CoordinatorGroups') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                                    stroke=""
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    fill="currentColor" />
                            </svg>

                            <span
                                class="menu-item-text"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                Gruppi Coordinatori
                            </span>

                            <svg
                                class="menu-item-arrow"
                                :class="[(selected === 'CoordinatorGroups') ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'lg:hidden' : '' ]"
                                width="20"
                                height="20"
                                viewBox="0 0 20 20"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585"
                                    stroke=""
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </a>

                        <!-- Dropdown Menu Start -->
                        <div
                            class="overflow-hidden transform translate"
                            :class="(selected === 'CoordinatorGroups') ? 'block' :'hidden'">
                            <ul
                                :class="sidebarToggle ? 'lg:hidden' : 'flex'"
                                class="flex flex-col gap-1 mt-2 menu-dropdown pl-9">
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/coordinator-group/index']) ?>"
                                        class="menu-dropdown-item group">
                                        Visualizza Gruppi
                                    </a>
                                </li>
                                <?php if (Yii::$app->user->can('create_coordinator_group')): ?>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/coordinator-group/create']) ?>"
                                        class="menu-dropdown-item group">
                                        Nuovo Gruppo
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <!-- Dropdown Menu End -->
                    </li>
                    <?php endif; ?>
                    <!-- Menu Item Coordinator Groups -->

                    <!-- Menu Item Therapists -->
                    <?php if (Yii::$app->user->can('create_therapist')): ?>
                    <li>
                        <a
                            href="#"
                            @click.prevent="selected = (selected === 'Therapists' ? '':'Therapists')"
                            class="menu-item group"
                            :class="(selected === 'Therapists') ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'Therapists') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M7.5 6C8.32843 6 9 6.67157 9 7.5C9 8.32843 8.32843 9 7.5 9C6.67157 9 6 8.32843 6 7.5C6 6.67157 6.67157 6 7.5 6ZM16.5 6C17.3284 6 18 6.67157 18 7.5C18 8.32843 17.3284 9 16.5 9C15.6716 9 15 8.32843 15 7.5C15 6.67157 15.6716 6 16.5 6ZM12 11C13.1046 11 14 11.8954 14 13C14 14.1046 13.1046 15 12 15C10.8954 15 10 14.1046 10 13C10 11.8954 10.8954 11 12 11ZM18.364 10.636C18.9497 11.2218 18.9497 12.1716 18.364 12.7574L17.6569 13.4645C17.2663 13.8551 16.633 13.8551 16.2425 13.4645C15.8519 13.074 15.8519 12.4408 16.2425 12.0503L16.9496 11.3431C17.5354 10.7574 18.4851 10.7574 19.0709 11.3431ZM5.636 10.636C6.22176 10.0503 7.17157 10.0503 7.75736 10.636L8.46447 11.3431C8.85499 11.7337 8.85499 12.3668 8.46447 12.7574C8.07394 13.1479 7.44078 13.1479 7.05025 12.7574L6.34315 12.0503C5.75736 11.4645 5.75736 10.5147 6.34315 9.92893ZM3 19.5C3 17.567 4.567 16 6.5 16H17.5C19.433 16 21 17.567 21 19.5C21 20.8807 19.8807 22 18.5 22H5.5C4.11929 22 3 20.8807 3 19.5Z"
                                    fill="" />
                            </svg>

                            <span
                                class="menu-item-text"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                Terapisti
                            </span>

                            <svg
                                class="menu-item-arrow"
                                :class="[(selected === 'Therapists') ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'lg:hidden' : '' ]"
                                width="20"
                                height="20"
                                viewBox="0 0 20 20"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585"
                                    stroke=""
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </a>

                        <!-- Dropdown Menu Start -->
                        <div
                            class="overflow-hidden transform translate"
                            :class="(selected === 'Therapists') ? 'block' :'hidden'">
                            <ul
                                :class="sidebarToggle ? 'lg:hidden' : 'flex'"
                                class="flex flex-col gap-1 mt-2 menu-dropdown pl-9">
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/therapist/index']) ?>"
                                        class="menu-dropdown-item group">
                                        Visualizza Terapisti
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/therapist/create']) ?>"
                                        class="menu-dropdown-item group">
                                        Nuovo Terapista
                                    </a>
                                </li>
                                <?php if (Yii::$app->user->can('view_own_group_therapists')): ?>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/therapist/my-group']) ?>"
                                        class="menu-dropdown-item group">
                                        I Miei Terapisti
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <!-- Dropdown Menu End -->
                    </li>
                    <?php elseif (Yii::$app->user->can('view_own_group_therapists')): ?>
                    <!-- Menu Item My Therapists (for coordinators only) -->
                    <li>
                        <a
                            href="<?= \yii\helpers\Url::to(['/therapist/my-group']) ?>"
                            @click="selected = (selected === 'MyTherapists' ? '':'MyTherapists')"
                            class="menu-item group"
                            :class="(selected === 'MyTherapists') ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'MyTherapists') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                                    stroke=""
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    fill="currentColor" />
                            </svg>

                            <span
                                class="menu-item-text"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                I Miei Terapisti
                            </span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <!-- Menu Item Therapists -->

                    <!-- Menu Item Patients -->
                    <?php if (Yii::$app->user->can('create_patient')): ?>
                    <li>
                        <a
                            href="#"
                            @click.prevent="selected = (selected === 'Patients' ? '':'Patients')"
                            class="menu-item group"
                            :class="(selected === 'Patients') ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'Patients') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M12 3.5C8.96243 3.5 6.5 5.96243 6.5 9C6.5 12.0376 8.96243 14.5 12 14.5C15.0376 14.5 17.5 12.0376 17.5 9C17.5 5.96243 15.0376 3.5 12 3.5ZM5 9C5 5.13401 8.13401 2 12 2C15.866 2 19 5.13401 19 9C19 12.866 15.866 16 12 16C8.13401 16 5 12.866 5 9ZM12 18.5C7.30558 18.5 3.5 20.3431 3.5 22.5C3.5 23.3284 4.17157 24 5 24H19C19.8284 24 20.5 23.3284 20.5 22.5C20.5 20.3431 16.6944 18.5 12 18.5ZM2 22.5C2 19.1863 6.47715 17 12 17C17.5228 17 22 19.1863 22 22.5C22 24.1569 20.6569 25.5 19 25.5H5C3.34315 25.5 2 24.1569 2 22.5Z"
                                    fill="" />
                            </svg>

                            <span
                                class="menu-item-text"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                Pazienti
                            </span>

                            <svg
                                class="menu-item-arrow"
                                :class="[(selected === 'Patients') ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'lg:hidden' : '' ]"
                                width="20"
                                height="20"
                                viewBox="0 0 20 20"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585"
                                    stroke=""
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </a>

                        <!-- Dropdown Menu Start -->
                        <div
                            class="overflow-hidden transform translate"
                            :class="(selected === 'Patients') ? 'block' :'hidden'">
                            <ul
                                :class="sidebarToggle ? 'lg:hidden' : 'flex'"
                                class="flex flex-col gap-1 mt-2 menu-dropdown pl-9">
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/patient/index']) ?>"
                                        class="menu-dropdown-item group">
                                        Visualizza Pazienti
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/patient/create']) ?>"
                                        class="menu-dropdown-item group">
                                        Nuovo Paziente
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <!-- Dropdown Menu End -->
                    </li>
                    <?php endif; ?>
                    <!-- Menu Item Patients -->

                    <!-- Menu Item Therapeutic Plans -->
                    <?php if (Yii::$app->user->can('view_therapeutic_plan')): ?>
                    <li>
                        <a
                            href="#"
                            @click.prevent="selected = (selected === 'TherapeuticPlans' ? '':'TherapeuticPlans')"
                            class="menu-item group"
                            :class="(selected === 'TherapeuticPlans') ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'TherapeuticPlans') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M6 2C4.89543 2 4 2.89543 4 4V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V8.41421C20 7.88378 19.7893 7.37507 19.4142 7L16 3.58579C15.6249 3.21071 15.1162 3 14.5858 3H6ZM6 4H14V8C14 9.10457 14.8954 10 16 10H18V20H6V4ZM16 8V6.41421L17.5858 8H16Z"
                                    fill="" />
                                <path
                                    d="M8 12C8 11.4477 8.44772 11 9 11H15C15.5523 11 16 11.4477 16 12C16 12.5523 15.5523 13 15 13H9C8.44772 13 8 12.5523 8 12Z"
                                    fill="" />
                                <path
                                    d="M8 16C8 15.4477 8.44772 15 9 15H13C13.5523 15 14 15.4477 14 16C14 16.5523 13.5523 17 13 17H9C8.44772 17 8 16.5523 8 16Z"
                                    fill="" />
                                <path
                                    d="M9 7C8.44772 7 8 7.44772 8 8C8 8.55228 8.44772 9 9 9H11C11.5523 9 12 8.55228 12 8C12 7.44772 11.5523 7 11 7H9Z"
                                    fill="" />
                            </svg>

                            <span
                                class="menu-item-text"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                Piani Terapeutici
                            </span>

                            <svg
                                class="menu-item-arrow"
                                :class="[(selected === 'TherapeuticPlans') ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'lg:hidden' : '' ]"
                                width="20"
                                height="20"
                                viewBox="0 0 20 20"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585"
                                    stroke=""
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </a>

                        <!-- Dropdown Menu Start -->
                        <div
                            class="overflow-hidden transform translate"
                            :class="(selected === 'TherapeuticPlans') ? 'block' :'hidden'">
                            <ul
                                :class="sidebarToggle ? 'lg:hidden' : 'flex'"
                                class="flex flex-col gap-1 mt-2 menu-dropdown pl-9">
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/therapeutic-plan/index']) ?>"
                                        class="menu-dropdown-item group">
                                        Visualizza Piani
                                    </a>
                                </li>
                                <?php if (Yii::$app->user->can('create_therapeutic_plan')): ?>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/therapeutic-plan/create']) ?>"
                                        class="menu-dropdown-item group">
                                        Nuovo Piano
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <!-- Dropdown Menu End -->
                    </li>
                    <?php endif; ?>
                    <!-- Menu Item Therapeutic Plans -->

                    <!-- Menu Item Document Requests -->
                    <?php if (Yii::$app->user->can('manage_documents') || Yii::$app->user->can('view_documents')): ?>
                    <li>
                        <a
                            href="<?= \yii\helpers\Url::to(['/document-request/index']) ?>"
                            @click="selected = (selected === 'DocumentRequests' ? '':'DocumentRequests')"
                            class="menu-item group"
                            :class="(selected === 'DocumentRequests') ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'DocumentRequests') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M6 2C4.89543 2 4 2.89543 4 4V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V8.41421C20 7.88378 19.7893 7.37507 19.4142 7L16 3.58579C15.6249 3.21071 15.1162 3 14.5858 3H6ZM6 4H14V8C14 9.10457 14.8954 10 16 10H18V20H6V4ZM16 8V6.41421L17.5858 8H16Z"
                                    fill="" />
                                <path
                                    d="M8 12C8 11.4477 8.44772 11 9 11H15C15.5523 11 16 11.4477 16 12C16 12.5523 15.5523 13 15 13H9C8.44772 13 8 12.5523 8 12Z"
                                    fill="" />
                                <path
                                    d="M8 16C8 15.4477 8.44772 15 9 15H13C13.5523 15 14 15.4477 14 16C14 16.5523 13.5523 17 13 17H9C8.44772 17 8 16.5523 8 16Z"
                                    fill="" />
                            </svg>

                            <span
                                class="menu-item-text"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                Richieste Documenti
                                <?php
                                // Mostra badge con richieste non lette solo se l'utente può gestirle
                                if (Yii::$app->user->can('manage_documents') || Yii::$app->user->can('view_documents')) {
                                    $unreadCount = \common\models\DocumentRequest::find()
                                        ->where(['status' => \common\models\RequestStatus::STATUS_INVIATA])
                                        ->count();
                                    
                                    if ($unreadCount > 0) {
                                        echo '<span class="ml-2 inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">' . $unreadCount . '</span>';
                                    }
                                }
                                ?>
                            </span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <!-- Menu Item Document Requests -->

                    
                </ul>
            </div>

           
        </nav>
        <!-- Sidebar Menu -->
    </div>
</aside>
<!-- ===== Sidebar End ===== -->