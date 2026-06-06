<script setup lang="ts">
import { ref } from 'vue'
import AppSidebar from './AppSidebar.vue'
import AppTopbar from './AppTopbar.vue'

const mobileSidebarOpen = ref(false)
</script>

<template>
  <div class="min-h-screen bg-app text-foreground">
    <div class="flex min-w-0">
      <AppSidebar :mobile-open="mobileSidebarOpen" @close="mobileSidebarOpen = false" />
      <div class="min-w-0 flex-1">
        <AppTopbar @menu="mobileSidebarOpen = true" />
        <main class="min-w-0 p-3 sm:p-4 lg:p-6">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>
