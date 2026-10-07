<script setup>
import ReaderHome from './components/ReaderHome.vue'
import AdminDashboard from './components/AdminDashboard.vue'
import Signin from './views/Auth/Signin.vue'
import Signup from './views/Auth/Signup.vue'

const currentPath = window.location.pathname.replace(/\/+$/, '') || '/'
</script>

<template>
  <AdminDashboard v-if="currentPath === '/admin'" />
  <Signin v-else-if="currentPath === '/signin'" />
  <Signup v-else-if="currentPath === '/signup'" />
  <ReaderHome v-else />
</template>
