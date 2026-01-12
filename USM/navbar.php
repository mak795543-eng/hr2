<header class="bg-base-100 shadow-sm z-10 border-b border-base-300 dark:border-gray-700" data-theme="light">
    <div class="px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <div class="flex items-center">
          <button onclick="toggleSidebar()" class="btn btn-ghost btn-sm hover:bg-base-300  transition-all hover:scale-105">
            <i data-lucide="menu" class="w-5 h-5"></i>
          </button>
        </div>
       <div class="flex items-center gap-4">
         <!-- Time Display -->
         <div class="animate-fadeIn">
           <span id="philippineTime" class="font-medium max-md:text-sm"></span>
         </div>
         
          <!-- Notification Dropdown -->
<div class="dropdown dropdown-end">

  
  <!-- Button -->
  <button id="notification-button" tabindex="0" class="btn btn-ghost btn-circle btn-sm relative">
    <i data-lucide="bell" class="w-5 h-5"></i>
    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
  </button>
  
  <!-- Dropdown Content - Responsive -->
  <ul tabindex="0" class="dropdown-content menu mt-3 z-[1] bg-[#001f54] rounded-lg shadow-xl overflow-hidden transform md:translate-x-0 sm:translate-x-1/2 sm:-translate-x-1/2">
    <!-- Header -->
    <li class="px-4 py-3 border-b  flex justify-between items-center sticky top-0 bg-[#001f54] backdrop-blur-sm z-10">
      <div class="flex items-center gap-2">
        <i data-lucide="bell" class="w-5 h-5 text-blue-300"></i>
        <span class="font-semibold text-white">Notifications</span>
      </div>
      <button class="text-blue-300 hover:text-white text-sm flex items-center gap-1">
        <i data-lucide="trash-2" class="w-4 h-4"></i>
        <span>Clear All</span>
      </button>
    </li>
    
    <!-- Notification Items Container - Scrollable -->
    <div class="max-h-96 overflow-y-auto">
      <!-- Notification items will be loaded here -->
    </div>
    
    <!-- Footer -->
    <li class="px-4 py-2 border-t  sticky bottom-0 bg-[#001f54] backdrop-blur-sm">
      <a class="text-center text-blue-300 hover:text-white text-sm flex items-center justify-center gap-1">
        <i data-lucide="list" class="w-4 h-4"></i>
        <span>View All Notifications</span>
      </a>
    </li>
  </ul>
</div>


          <!-- User Dropdown -->
          <div class="dropdown dropdown-end">
  <label tabindex="0" class="btn btn-ghost btn-circle avatar">
    <div class="w-8 rounded-full">
      <img src="" alt="User Avatar" />
    </div>
  </label>
  <ul tabindex="0" class="dropdown-content menu mt-1 z-[100] w-52 bg-[#001f54] rounded-box shadow-xl">
    <!-- User Profile Section -->
    <li class="p-3 border-b ">
      <div class="bg-blue-700/50 rounded-md shadow-md flex items-center gap-3">
        <div class="avatar">
          <div class="w-10 rounded-full">
            <img src="" alt="User Avatar" class="dark:brightness-90" />
          </div>
        </div>
        <div>
          <p class="font-medium text-white"></p>
          <p class="text-xs text-white"></p>
        </div>
      </div>
    </li>
    
    <!-- Menu Items -->
    <li>
      <a class="flex items-center gap-2 px-4 py-2 text-white hover:bg-blue-700/50 transition-colors">
        <i data-lucide="user" class="w-4 h-4"></i>
        <span>Profile</span>
      </a>
    </li>
    <li>
      <a class="flex items-center gap-2 px-4 py-2 text-white hover:bg-blue-700/50 transition-colors">
        <i data-lucide="settings" class="w-4 h-4"></i>
        <span>Settings</span>
      </a>
    </li>
    <li class="">
      <a class="flex items-center gap-2 px-4 py-2 text-white hover:bg-blue-700/50 transition-colors">
        <i data-lucide="log-out" class="w-4 h-4"></i>
        <span>Sign out</span>
      </a>
    </li>
  </ul>
</div>
        </div>
      </div>
    </div>
  </header>


<style>
  @media (max-width: 767px) {
    .dropdown-content {
      left: 50% !important;
      transform: translateX(-80%) !important;
    }
  }
</style>