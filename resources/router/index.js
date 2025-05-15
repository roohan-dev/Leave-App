import { createRouter, createWebHistory } from 'vue-router'

// Import your page components
import Dashboard from '../Pages/Dashboard.vue'
import LeaveIndex from '../Pages/Leave/Index.vue'
import LeaveCreate from '../Pages/Leave/Create.vue'
import LeaveEdit from '../Pages/Leave/Edit.vue'
import Profile from '../Pages/Profile/Edit.vue'

const routes = [
    {
        path: '/dashboard',
        name: 'dashboard',
        component: Dashboard,
        meta: { requiresAuth: true }
    },
    {
        path: '/leave-requests',
        name: 'leave-requests.index',
        component: LeaveIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/leave-requests/create',
        name: 'leave-requests.create',
        component: LeaveCreate,
        meta: { requiresAuth: true }
    },
    {
        path: '/leave-requests/:id/edit',
        name: 'leave-requests.edit',
        component: LeaveEdit,
        meta: { requiresAuth: true },
        props: true
    },
    {
        path: '/profile',
        name: 'profile.edit',
        component: Profile,
        meta: { requiresAuth: true }
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

// Navigation guard for authentication
router.beforeEach((to, from, next) => {
    if (to.meta.requiresAuth && !window.Laravel.user) {
        window.location.href = '/login'
        return
    }
    next()
})

// Navigation guard for admin routes
router.beforeEach((to, from, next) => {
    if (to.meta.requiresAdmin && !window.Laravel.user?.isAdmin) {
        next({ name: 'dashboard' })
        return
    }
    next()
})

export default router
