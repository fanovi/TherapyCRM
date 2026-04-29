<?php

use yii\helpers\Url;

// Ottieni informazioni sulla pagina corrente
$currentController = Yii::$app->controller->id;
$currentAction = Yii::$app->controller->action->id;
$currentRoute = $currentController . '/' . $currentAction;

// Definisci le mappature dei menu
$menuMappings = [
    'Dashboard' => ['site/index'],
    'Calendar' => ['calendar/index'],
    'Statistics' => ['statistics/index', 'statistics/absences', 'statistics/patients', 'statistics/treatments', 'statistics/plans'],
    'Communications' => ['communication/index', 'communication/view'],
    'Notifications' => ['notification/index', 'notification/view'],
    'Administrators' => ['user/administrators', 'user/create-administrator', 'user/view-administrator', 'user/update-administrator'],
    'Managers' => ['user/managers', 'user/create-manager', 'user/view-manager', 'user/update-manager'],
    'Coordinators' => [
        'user/coordinators', 'user/create-coordinator', 'user/view-coordinator', 'user/update-coordinator',
        'coordinator-group/index', 'coordinator-group/create', 'coordinator-group/view', 'coordinator-group/update',
    ],
    'Therapists' => ['therapist/index', 'therapist/create', 'therapist/view', 'therapist/update', 'therapist/my-group'],
    'MyTherapists' => ['therapist/my-group'],
    'Patients' => ['patient/index', 'patient/create', 'patient/view', 'patient/update', 'patient/accounts', 'patient/view-account', 'patient/my-group'],
    'MyPatients' => ['patient/my-group'],
    'TherapeuticPlans' => ['therapeutic-plan/index', 'therapeutic-plan/create', 'therapeutic-plan/view', 'therapeutic-plan/update'],
    'DocumentRequests' => ['document-request/index', 'document-request/view', 'document-request/update'],
    'Complaints' => ['complaint/index', 'complaint/view'],
    'Absences' => ['absence/index', 'absence/patients', 'absence/create', 'absence/view', 'absence/update', 'absence/daily'],
    'Manuale' => ['site/manuale', 'site/manuale-gestionale', 'site/manuale-app'],
    'RolePermissions' => ['permission/roles', 'permission/view-role'],
];

// Funzione per controllare se un menu è attivo
function isMenuActive($menuKey, $currentRoute, $mappings)
{
    if (!isset($mappings[$menuKey]))
        return false;
    return in_array($currentRoute, $mappings[$menuKey]);
}

// Funzione per controllare se un sottomenu è attivo
function isSubmenuActive($routes, $currentRoute)
{
    return in_array($currentRoute, $routes);
}

// Funzione per determinare il menu attivo corrente
function getCurrentActiveMenu($currentRoute, $mappings)
{
    foreach ($mappings as $menuKey => $routes) {
        if (in_array($currentRoute, $routes)) {
            return $menuKey;
        }
    }
    return 'Dashboard';  // Default
}
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
                <img class="dark:hidden" src="<?= Url::to('@web/images/logo/LOGO_orizzontale-1.svg') ?>" style="min-height: 70px;" alt="Logo" />
                <img
                    class="hidden dark:block"
                    src="<?= Url::to('@web/images/logo/LOGO_orizzontale-1.svg') ?>"
                    style="min-height: 70px;"
                    alt="Logo" />
            </span>

            <img
                class="logo-icon"
                :class="sidebarToggle ? 'lg:block' : 'hidden'"
                src="<?= Url::to('@web/images/logo/cropped-flavicon-192x192.png') ?>"
                alt="Logo" />
        </a>
    </div>
    <!-- SIDEBAR HEADER -->

    <div
        class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">
        <!-- Sidebar Menu -->
        <nav x-data="{selected: '<?= getCurrentActiveMenu($currentRoute, $menuMappings) ?>'}" x-init="selected = '<?= getCurrentActiveMenu($currentRoute, $menuMappings) ?>'">
            <!-- Menu Group -->
            <div>
                <h3 class="mb-4 text-xs uppercase leading-[20px] text-gray-400">
                   

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
                            href="<?= Url::to(['site/index']) ?>"
                            class="menu-item group"
                            :class="(selected === 'Dashboard') || <?= isMenuActive('Dashboard', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'Dashboard') || <?= isMenuActive('Dashboard', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
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

                    <!-- Menu Item Patients -->
                    <?php if (Yii::$app->user->can('create_patient')): ?>
                    <li>
                        <a
                            href="#"
                            @click.prevent="selected = (selected === 'Patients' ? '':'Patients')"
                            class="menu-item group"
                            :class="(selected === 'Patients') || <?= isMenuActive('Patients', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'Patients') || <?= isMenuActive('Patients', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
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
                                :class="[(selected === 'Patients') || <?= isMenuActive('Patients', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'lg:hidden' : '' ]"
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
                            :class="(selected === 'Patients') || <?= isMenuActive('Patients', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'block' :'hidden'">
                            <ul
                                :class="sidebarToggle ? 'lg:hidden' : 'flex'"
                                class="flex flex-col gap-1 mt-2 menu-dropdown pl-9">
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/patient/index']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['patient/index'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Visualizza Pazienti
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/patient/create']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['patient/create'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Nuovo Paziente
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/patient/accounts']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['patient/accounts', 'patient/view-account'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Account
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <!-- Dropdown Menu End -->
                    </li>
                    <?php elseif (Yii::$app->user->can('view_own_group_patients') && Yii::$app->user->can('coordinator')): ?>
                    <!-- Menu Item My Patients (for coordinators only) -->
                    <li>
                        <a
                            href="<?= \yii\helpers\Url::to(['/patient/my-group']) ?>"
                            @click="selected = (selected === 'MyPatients' ? '':'MyPatients')"
                            class="menu-item group"
                            :class="(selected === 'MyPatients') || <?= isMenuActive('MyPatients', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'MyPatients') || <?= isMenuActive('MyPatients', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
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
                                I Miei Pazienti
                            </span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <!-- Menu Item Patients -->

                    <!-- Menu Item Therapists -->
                    <?php if (Yii::$app->user->can('create_therapist')): ?>
                    <li>
                        <a
                            href="#"
                            @click.prevent="selected = (selected === 'Therapists' ? '':'Therapists')"
                            class="menu-item group"
                            :class="(selected === 'Therapists') || <?= isMenuActive('Therapists', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'Therapists') || <?= isMenuActive('Therapists', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
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
                                :class="[(selected === 'Therapists') || <?= isMenuActive('Therapists', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'lg:hidden' : '' ]"
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
                            :class="(selected === 'Therapists') || <?= isMenuActive('Therapists', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'block' :'hidden'">
                            <ul
                                :class="sidebarToggle ? 'lg:hidden' : 'flex'"
                                class="flex flex-col gap-1 mt-2 menu-dropdown pl-9">
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/therapist/index']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['therapist/index'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Visualizza Terapisti
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/therapist/create']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['therapist/create'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Nuovo Terapista
                                    </a>
                                </li>
                                <?php if (Yii::$app->user->can('view_own_group_therapists') && Yii::$app->user->can('coordinator')): ?>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/therapist/my-group']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['therapist/my-group'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        I Miei Terapisti
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <!-- Dropdown Menu End -->
                    </li>
                    <?php elseif (Yii::$app->user->can('view_own_group_therapists') && Yii::$app->user->can('coordinator')): ?>
                    <!-- Menu Item My Therapists (for coordinators only) -->
                    <li>
                        <a
                            href="<?= \yii\helpers\Url::to(['/therapist/my-group']) ?>"
                            @click="selected = (selected === 'MyTherapists' ? '':'MyTherapists')"
                            class="menu-item group"
                            :class="(selected === 'MyTherapists') || <?= isMenuActive('MyTherapists', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'MyTherapists') || <?= isMenuActive('MyTherapists', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
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
                                I Miei Terapisti
                            </span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <!-- Menu Item Therapists -->

                    <!-- Menu Item Absences -->
                    <?php if (Yii::$app->user->can('create_absence') || Yii::$app->user->can('view_absence')): ?>
                    <li>
                        <a
                            href="#"
                            @click.prevent="selected = (selected === 'Absences' ? '':'Absences')"
                            class="menu-item group"
                            :class="(selected === 'Absences') || <?= isMenuActive('Absences', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'Absences') || <?= isMenuActive('Absences', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM12 20c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"
                                    fill="" />
                            </svg>

                            <span
                                class="menu-item-text"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                Assenze
                            </span>

                            <svg
                                class="menu-item-arrow"
                                :class="[(selected === 'Absences') || <?= isMenuActive('Absences', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'lg:hidden' : '' ]"
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
                            :class="(selected === 'Absences') || <?= isMenuActive('Absences', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'block' :'hidden'">
                            <ul
                                :class="sidebarToggle ? 'lg:hidden' : 'flex'"
                                class="flex flex-col gap-1 mt-2 menu-dropdown pl-9">
                                <?php if (Yii::$app->user->can('view_absence')): ?>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/absence/index']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['absence/index'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Assenze Terapisti
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if (Yii::$app->user->can('view_patient_absence')): ?>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/absence/patients']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['absence/patients'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Assenze Pazienti
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if (Yii::$app->user->can('view_absence')): ?>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/absence/daily']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['absence/daily'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Presenze Giornaliere
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if (Yii::$app->user->can('create_absence')): ?>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/absence/create']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['absence/create'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Nuova Assenza Terapista
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <!-- Dropdown Menu End -->
                    </li>
                    <?php endif; ?>
                    <!-- Menu Item Absences -->

                    <!-- Menu Item Therapeutic Plans -->
                    <?php if (Yii::$app->user->can('view_therapeutic_plan')): ?>
                    <li>
                        <a
                            href="#"
                            @click.prevent="selected = (selected === 'TherapeuticPlans' ? '':'TherapeuticPlans')"
                            class="menu-item group"
                            :class="(selected === 'TherapeuticPlans') || <?= isMenuActive('TherapeuticPlans', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'TherapeuticPlans') || <?= isMenuActive('TherapeuticPlans', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
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
                                :class="[(selected === 'TherapeuticPlans') || <?= isMenuActive('TherapeuticPlans', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'lg:hidden' : '' ]"
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
                            :class="(selected === 'TherapeuticPlans') || <?= isMenuActive('TherapeuticPlans', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'block' :'hidden'">
                            <ul
                                :class="sidebarToggle ? 'lg:hidden' : 'flex'"
                                class="flex flex-col gap-1 mt-2 menu-dropdown pl-9">
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/therapeutic-plan/index']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['therapeutic-plan/index'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Visualizza Piani
                                    </a>
                                </li>
                                <?php if (Yii::$app->user->can('create_therapeutic_plan')): ?>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/therapeutic-plan/create']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['therapeutic-plan/create'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
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
                            :class="(selected === 'DocumentRequests') || <?= isMenuActive('DocumentRequests', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'DocumentRequests') || <?= isMenuActive('DocumentRequests', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
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
                                        echo '<span class="ml-2 inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full notification-badge-red">' . $unreadCount . '</span>';
                                    }
                                }
                                ?>
                            </span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <!-- Menu Item Document Requests -->

                    <!-- Menu Item Complaints -->
                    <?php if (Yii::$app->user->can('view_complaints')): ?>
                    <li>
                        <a
                            href="<?= \yii\helpers\Url::to(['/complaint/index']) ?>"
                            @click="selected = (selected === 'Complaints' ? '':'Complaints')"
                            class="menu-item group"
                            :class="(selected === 'Complaints') || <?= isMenuActive('Complaints', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'Complaints') || <?= isMenuActive('Complaints', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4V4c0-1.1-.9-2-2-2zm-7 9h-2V5h2v6zm0 4h-2v-2h2v2z"
                                    fill="" />
                            </svg>

                            <span
                                class="menu-item-text"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                Reclami
                            </span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <!-- Menu Item Complaints -->

                    <!-- Menu Item Notifications -->
                    <?php if (Yii::$app->user->can('view_notifications')): ?>
                    <li>
                        <a
                            href="<?= \yii\helpers\Url::to(['/notification/index']) ?>"
                            @click="selected = (selected === 'Notifications' ? '':'Notifications')"
                            class="menu-item group"
                            :class="(selected === 'Notifications') || <?= isMenuActive('Notifications', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'Notifications') || <?= isMenuActive('Notifications', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M12 2C13.1046 2 14 2.89543 14 4C14 5.10457 13.1046 6 12 6C10.8954 6 10 5.10457 10 4C10 2.89543 10.8954 2 12 2ZM12 8C14.7614 8 17 10.2386 17 13V16.5C17 16.7761 17.2239 17 17.5 17H19C19.5523 17 20 17.4477 20 18C20 18.5523 19.5523 19 19 19H5C4.44772 19 4 18.5523 4 18C4 17.4477 4.44772 17 5 17H6.5C6.77614 17 7 16.7761 7 16.5V13C7 10.2386 9.23858 8 12 8ZM12 20C11.4477 20 11 20.4477 11 21C11 21.5523 11.4477 22 12 22C12.5523 22 13 21.5523 13 21C13 20.4477 12.5523 20 12 20Z"
                                    fill="" />
                            </svg>

                            <span
                                class="menu-item-text"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                Notifiche Sistema
                                <?php
                                // Mostra badge con notifiche non lette
                                $unreadNotifications = \common\models\Notification::find()
                                    ->where(['not', ['sender_user_id' => null]])  // Solo notifiche con mittente
                                    ->andWhere(['read_at' => null])
                                    ->count();

                                if ($unreadNotifications > 0) {
                                    echo '<span class="ml-2 inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full notification-badge-red">' . $unreadNotifications . '</span>';
                                }
                                ?>
                            </span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <!-- Menu Item Notifications -->

                    <!-- Menu Item Statistics -->
                    <?php if (Yii::$app->user->can('view_statistics')): ?>
                    <li>
                        <a
                            href="#"
                            @click.prevent="selected = (selected === 'Statistics' ? '':'Statistics')"
                            class="menu-item group"
                            :class="(selected === 'Statistics') || <?= isMenuActive('Statistics', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'Statistics') || <?= isMenuActive('Statistics', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
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

                            <svg
                                class="menu-item-arrow"
                                :class="[(selected === 'Statistics') || <?= isMenuActive('Statistics', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'lg:hidden' : '' ]"
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
                            :class="(selected === 'Statistics') || <?= isMenuActive('Statistics', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'block' :'hidden'">
                            <ul
                                :class="sidebarToggle ? 'lg:hidden' : 'flex'"
                                class="flex flex-col gap-1 mt-2 menu-dropdown pl-9">
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/statistics/index']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['statistics/index'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Dashboard Generale
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/statistics/absences']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['statistics/absences'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Analisi Assenze
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/statistics/patients']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['statistics/patients'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Analisi Pazienti
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/statistics/treatments']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['statistics/treatments'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Analisi Trattamenti
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/statistics/plans']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['statistics/plans'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Analisi Piani
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <!-- Dropdown Menu End -->
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
                            :class="(selected === 'Administrators') || <?= isMenuActive('Administrators', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'Administrators') || <?= isMenuActive('Administrators', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
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
                                :class="[(selected === 'Administrators') || <?= isMenuActive('Administrators', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'lg:hidden' : '' ]"
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
                            :class="(selected === 'Administrators') || <?= isMenuActive('Administrators', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'block' :'hidden'">
                            <ul
                                :class="sidebarToggle ? 'lg:hidden' : 'flex'"
                                class="flex flex-col gap-1 mt-2 menu-dropdown pl-9">
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/user/administrators']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['user/administrators'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Visualizza Amministratori
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/user/create-administrator']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['user/create-administrator'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
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
                    <?php if (Yii::$app->user->can('create_coordinator') || Yii::$app->user->can('view_coordinator_group')): ?>
                    <li>
                        <a
                            href="#"
                            @click.prevent="selected = (selected === 'Coordinators' ? '':'Coordinators')"
                            class="menu-item group"
                            :class="(selected === 'Coordinators') || <?= isMenuActive('Coordinators', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'Coordinators') || <?= isMenuActive('Coordinators', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
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
                                :class="[(selected === 'Coordinators') || <?= isMenuActive('Coordinators', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'lg:hidden' : '' ]"
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
                            :class="(selected === 'Coordinators') || <?= isMenuActive('Coordinators', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'block' :'hidden'">
                            <ul
                                :class="sidebarToggle ? 'lg:hidden' : 'flex'"
                                class="flex flex-col gap-1 mt-2 menu-dropdown pl-9">
                                <?php if (Yii::$app->user->can('create_coordinator')): ?>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/user/coordinators']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['user/coordinators'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Visualizza Coordinatori
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/user/create-coordinator']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['user/create-coordinator'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Nuovo Coordinatore
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if (Yii::$app->user->can('view_coordinator_group')): ?>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/coordinator-group/index']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['coordinator-group/index'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Visualizza Gruppi
                                    </a>
                                </li>
                                <?php if (Yii::$app->user->can('create_coordinator_group')): ?>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/coordinator-group/create']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['coordinator-group/create'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Nuovo Gruppo
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <!-- Dropdown Menu End -->
                    </li>
                    <?php endif; ?>
                    <!-- Menu Item Coordinators -->

                    <!-- Menu Item Managers -->
                    <?php if (Yii::$app->user->can('create_manager')): ?>
                    <li>
                        <a
                            href="#"
                            @click.prevent="selected = (selected === 'Managers' ? '':'Managers')"
                            class="menu-item group"
                            :class="(selected === 'Managers') || <?= isMenuActive('Managers', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-active' : 'menu-item-inactive'">
                            <svg
                                :class="(selected === 'Managers') || <?= isMenuActive('Managers', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                    stroke=""
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    fill="currentColor" />
                                <path
                                    d="M18 8a3 3 0 11-6 0 3 3 0 016 0zM18 15v6M21 18h-6"
                                    stroke=""
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>

                            <span
                                class="menu-item-text"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                Manager
                            </span>

                            <svg
                                class="menu-item-arrow"
                                :class="[(selected === 'Managers') || <?= isMenuActive('Managers', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'lg:hidden' : '' ]"
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
                            :class="(selected === 'Managers') || <?= isMenuActive('Managers', $currentRoute, $menuMappings) ? 'true' : 'false' ?> ? 'block' :'hidden'">
                            <ul
                                :class="sidebarToggle ? 'lg:hidden' : 'flex'"
                                class="flex flex-col gap-1 mt-2 menu-dropdown pl-9">
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/user/managers']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['user/managers'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Visualizza Manager
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="<?= \yii\helpers\Url::to(['/user/create-manager']) ?>"
                                        class="menu-dropdown-item group <?= isSubmenuActive(['user/create-manager'], $currentRoute) ? 'menu-dropdown-item-active' : '' ?>">
                                        Nuovo Manager
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <!-- Dropdown Menu End -->
                    </li>
                    <?php endif; ?>
                    <!-- Menu Item Managers -->


                    <!-- Menu Item Permessi Ruoli -->
                    <?php if (Yii::$app->user->can('manage_permissions')): ?>
                    <li>
                        <a
                            href="<?= \yii\helpers\Url::to(['/permission/roles']) ?>"
                            class="menu-item group"
                            :class="'<?= isMenuActive('RolePermissions', $currentRoute, $menuMappings) ? 'menu-item-active' : 'menu-item-inactive' ?>'">
                            <svg
                                :class="'<?= isMenuActive('RolePermissions', $currentRoute, $menuMappings) ? 'menu-item-icon-active' : 'menu-item-icon-inactive' ?>'"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M12 1.5C9.37 1.5 7.23 3.64 7.23 6.27C7.23 8.9 9.37 11.04 12 11.04C14.63 11.04 16.77 8.9 16.77 6.27C16.77 3.64 14.63 1.5 12 1.5ZM9.23 6.27C9.23 4.74 10.47 3.5 12 3.5C13.53 3.5 14.77 4.74 14.77 6.27C14.77 7.8 13.53 9.04 12 9.04C10.47 9.04 9.23 7.8 9.23 6.27ZM5.5 19.5V20.5H18.5V19.5C18.5 16.74 14.69 14.5 12 14.5C9.31 14.5 5.5 16.74 5.5 19.5ZM3.5 19.5C3.5 15.36 7.86 12.5 12 12.5C16.14 12.5 20.5 15.36 20.5 19.5V21.5C20.5 22.05 20.05 22.5 19.5 22.5H4.5C3.95 22.5 3.5 22.05 3.5 21.5V19.5Z"
                                    fill="" />
                            </svg>

                            <span
                                class="menu-item-text"
                                :class="sidebarToggle ? 'lg:hidden' : ''">
                                Permessi Ruoli
                            </span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <!-- Menu Item Permessi Ruoli End -->

                    <!-- Menu Item Manuale d'Uso (nascosto) -->
                    <!-- Menu Item Manuale d'Uso End -->

                </ul>
            </div>

           
        </nav>
        <!-- Sidebar Menu -->
    </div>
</aside>
<!-- ===== Sidebar End ===== -->