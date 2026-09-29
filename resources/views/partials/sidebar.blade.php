<nav class="sidebar sidebar-offcanvas mt-0" id="sidebar">
    <!-- User Profile Section -->
    <div class="sidebar-profile text-center p-3">
        @if (auth()->user()->profile_pic)
            <img src="{{ 'data:image/jpeg;base64,' . auth()->user()->profile_pic }}" alt="Profile Picture"
                class="rounded-circle shadow" style="width: 80px; height: 80px; object-fit: cover;">
        @else
            <i class="bi bi-person-circle" style="font-size: 3rem; color: #fff;"></i>
        @endif

        <div class="sidebar-profile-info mt-2">
            <span class="sidebar-profile-name truncate-text">{{ auth()->user()->name }}</span>
            <span class="sidebar-profile-email text-light truncate-text">
                <small>{{ auth()->user()->email }}</small>
            </span>
        </div>
        <div class="d-block d-sm-none mt-1">
            <small>Referral Code:</small>
            <p class="badge bg-danger">{{ ucwords(auth()->user()->referral_code) }}</p>
        </div>
    </div>

    <ul class="nav">

        <!-- Dashboard Section -->
        <li class="nav-item">
            <a class="nav-link {{ Route::is('user.dashboard') || Route::is('admin.dashboard') ? 'active' : '' }}" 
               href="{{ auth()->user()->role == 'admin' ? route('admin.dashboard') : route('user.dashboard') }}">
                <i class="mdi mdi-view-dashboard menu-icon"></i>
                <span class="menu-title">Dashboard</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ Route::is('user.wallet') ? 'active' : '' }}" href="{{ route('user.wallet') }}">
                <i class="mdi mdi-wallet menu-icon"></i>
                <span class="menu-title">Fund Wallet</span>
            </a>
        </li>

          <li class="nav-item">
            <a class="nav-link {{ Route::is('user.airtime') ? 'active' : '' }}" href="{{ route('user.airtime') }}">
                <i class="mdi mdi-phone menu-icon"></i>
                <span class="menu-title">Buy Airtime</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Route::is('user.buy-data') ? 'active' : '' }}" href="{{ route('user.buy-data') }}">
                <i class="mdi mdi-database-outline menu-icon"></i>
                <span class="menu-title">Buy Data</span>
            </a>
        </li>

         <li class="nav-item">
            <a class="nav-link {{ Route::is('user.buy-sme-data') ? 'active' : '' }}" href="{{ route('user.buy-sme-data') }}">
                <i class="mdi mdi-wifi-cog menu-icon"></i>
                <span class="menu-title">Buy SME Data</span>
            </a>
        </li>

        <!-- Identity & Verification -->
        <li class="nav-item">
            <a href="#" class="nav-link" onclick="toggleSubmenu(event, 'verificationSubmenu')">
                <i class="mdi mdi-shield-check-outline menu-icon"></i>
                <span class="menu-title">Verification</span>
                <i class="mdi mdi-chevron-down ms-auto"></i>
            </a>
            <ul class="sub-menu nav flex-column ps-4" id="verificationSubmenu">
                <li class="nav-item">
                    <a class="nav-link {{ Route::is('user.nin.verification.index') ? 'active' : '' }}"
                        href="{{ route('user.nin.verification.index') }}">
                        <i class="mdi mdi-account-search-outline menu-icon"></i> NIN Verification
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::is('user.nin.phone.index') ? 'active' : '' }}"
                        href="{{ route('user.nin.phone.index') }}">
                        <i class="mdi mdi-card-text-outline menu-icon"></i> NIN Phone
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::is('user.nin.demo.index') ? 'active' : '' }}"
                        href="{{ route('user.nin.demo.index') }}">
                        <i class="mdi mdi-card-text-outline menu-icon"></i> NIN Demographic
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ Route::is('user.bvn-verification') ? 'active' : '' }}"
                        href="{{ route('user.bvn-verification') }}">
                        <i class="mdi mdi-shield-search menu-icon"></i> BVN verification
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::is('user.tin.index') ? 'active' : '' }}"
                        href="{{ route('user.tin.index') }}">
                        <i class="mdi mdi-file-percent-outline menu-icon"></i> TIN Verification
                    </a>
                </li>
            </ul>
        </li>


         <!-- Nin Services Section -->
        <li class="nav-item">
            <a href="#" class="nav-link" onclick="toggleSubmenu(event, 'ninSubmenu')">
                <i class="mdi mdi-account-cog-outline menu-icon"></i>
                <span class="menu-title">Nin Services</span>
                <i class="mdi mdi-chevron-down ms-auto"></i>
            </a>
            <ul class="sub-menu nav flex-column ps-4" id="ninSubmenu">
                <li class="nav-item">
                    <a class="nav-link {{ Route::is('user.nin.validation.index') ? 'active' : '' }}"
                        href="{{ route('user.nin.validation.index') }}">
                        <i class="mdi mdi-account-check-outline menu-icon"></i> NIN Validation
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::is('user.nin.modification.index') ? 'active' : '' }}"
                        href="{{ route('user.nin.modification.index') }}">
                        <i class="mdi mdi-account-edit-outline menu-icon"></i> NIN Modification
                    </a>
                </li>
              
                <li class="nav-item">
                    <a class="nav-link {{ Route::is('user.ipe.index') ? 'active' : '' }}"
                        href="{{ route('user.ipe.index') }}">
                        <i class="mdi mdi-file-check-outline menu-icon"></i> IPE Clearance
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::is('user.nin.personalisation.*') ? 'active' : '' }}"
                        href="{{ route('user.nin.personalisation.index') }}">
                        <i class="mdi mdi-card-account-details-outline menu-icon"></i> NIN Personalisation
                    </a>
                </li>
            </ul>
        </li>
        <!--end nin services Section -->


            <!-- bvn services Section -->
           <li class="nav-item">
            <a href="#" class="nav-link" onclick="toggleSubmenu(event, 'bvnSubmenu')">
                <i class="mdi mdi-bank-outline menu-icon"></i>
                <span class="menu-title">BVN Services</span>
                <i class="mdi mdi-chevron-down ms-auto"></i>
            </a>
            <ul class="sub-menu nav flex-column ps-4" id="bvnSubmenu">
                <li class="nav-item">
                    <a class="nav-link {{ Route::is('user.modification') ? 'active' : '' }}"
                        href="{{ route('user.modification') }}">
                        <i class="mdi mdi-account-edit-outline menu-icon"></i> BVN Modification
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::is('user.bvn-crm') ? 'active' : '' }}"
                        href="{{ route('user.bvn-crm') }}">
                        <i class="mdi mdi-account-details-outline menu-icon"></i> BVN CRM
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::is('user.phone.search.*') ? 'active' : '' }}"
                        href="{{ route('user.phone.search.index') }}">
                        <i class="mdi mdi-phone-search-outline menu-icon"></i> BVN Search
                    </a>
                </li>
            </ul>
        </li>
        <!--end bvn services Section -->

        <li class="nav-item">
            <a class="nav-link" href="{{ route('user.support') }}">
                <i class="mdi mdi-lifebuoy menu-icon"></i>
                <span class="menu-title">Support</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Route::is('user.profile') ? 'active' : '' }}" href="{{ route('user.profile') }}">
                <i class="mdi mdi-account-circle menu-icon"></i>
                <span class="menu-title">Profile</span>
            </a>
        </li>
        <!-- Admin Section -->
        @if (auth()->user()->role == 'admin')
            <li class="nav-item">
                <a href="#" class="nav-link" onclick="toggleSubmenu(event, 'adminSubmenu')">
                    <i class="mdi mdi-cog-outline menu-icon"></i>
                    <span class="menu-title">Manage</span>
                    <i class="mdi mdi-chevron-down ms-auto"></i>
                </a>
                <ul class="sub-menu nav flex-column ps-4" id="adminSubmenu">
                    <li class="nav-item">
                        <a class="nav-link {{ Route::is('admin.users.index') ? 'active' : '' }}"
                            href="{{ route('admin.users.index') }}">
                            <i class="mdi mdi-account-multiple menu-icon"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Route::is('admin.services.index') ? 'active' : '' }}" href="{{ route('admin.services.index') }}">
                            <i class="mdi mdi-layers-outline menu-icon"></i> Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Route::is('admin.services.bvn-crm.index') ? 'active' : '' }}" href="{{ route('admin.services.bvn-crm.index') }}">
                            <i class="mdi mdi-account-details-outline menu-icon"></i> BVN CRM & Search
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Route::is('admin.services.nin-personalisation.*') ? 'active' : '' }}" href="{{ route('admin.services.nin-personalisation.index') }}">
                            <i class="mdi mdi-card-account-details-outline menu-icon"></i> NIN Personalisation
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Route::is('admin.data-variations.*') ? 'active' : '' }}" href="{{ route('admin.data-variations.index') }}">
                            <i class="mdi mdi-database-cog menu-icon"></i> Data Variations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Route::is('admin.sme-data.*') ? 'active' : '' }}" href="{{ route('admin.sme-data.index') }}">
                            <i class="mdi mdi-wifi-cog menu-icon"></i> SME Data Plans
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Route::is('admin.transactions') ? 'active' : '' }}"
                            href="{{ route('admin.transactions') }}">
                            <i class="mdi mdi-receipt-text-outline menu-icon"></i>
                            All Transactions
                        </a>
                    </li>
                </ul>
            </li>
        @endif
        <!-- Logout Section -->
        <li class="nav-item">
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <a class="nav-link d-flex align-items-center" style="margin-left:14px;" href="#"
                    onclick="event.preventDefault(); this.closest('form').submit();">
                    <i class="mdi mdi-logout menu-icon"></i>
                    <span class="menu-title">Logout</span>
                </a>
            </form>
        </li>
    </ul>
</nav>
