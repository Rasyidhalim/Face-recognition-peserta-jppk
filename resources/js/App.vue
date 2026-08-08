<template>
  <Login 
    v-if="currentPage === 'login'" 
    @login-success="(role) => handleLoginSuccess(role)" 
  />
  
  <Dashboard 
    v-else-if="currentPage === 'dashboard'" 
    :user-role="currentUserRole"
    @do-logout="handleLogout" 
  />
</template>

<script setup>
import Login from './Pages/Login.vue'
import Dashboard from './Pages/Dashboard.vue'
import { useApp } from './Composables/useApp'

const {
  currentPage,
  currentUserRole,
  handleLoginSuccess,
  handleLogout
} = useApp()
</script>