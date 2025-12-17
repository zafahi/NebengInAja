<template>
  <div id="app">
    <Navbar v-if="!isAuthPage" />
    <main :class="{ 'with-navbar': !isAuthPage }">
      <router-view />
    </main>
    <Footer v-if="!isAuthPage" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import Navbar from './components/Navbar.vue'
import Footer from './components/Footer.vue'

const route = useRoute()

const isAuthPage = computed(() => {
  return ['Login', 'Register'].includes(route.name)
})
</script>

<style>
#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

main {
  flex: 1;
}

main.with-navbar {
  padding-top: 70px;
}
</style>
