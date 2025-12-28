<!-- resources/js/Pages/CRM/Index.vue -->
<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import draggable from 'vuedraggable';

const props = defineProps({
    projects: Object,
    stageValues: Object,
    stats: Object,
    companies: Array,
    contacts: Array,
    stages: Object,
});

const showProjectModal = ref(false);
const showCompanyModal = ref(false);
const showContactModal = ref(false);
const editingProject = ref(null);

const projectForm = ref({
    company_id: null,
    name: '',
    value: 0,
    stage: 'lead',
    notes: '',
    contact_ids: [],
});

const companyForm = ref({
    name: '',
    email: '',
    phone: '',
    notes: '',
});

const contactForm = ref({
    company_id: null,
    name: '',
    email: '',
    phone: '',
    title: '',
    notes: '',
});

// Organize projects by stage
const kanbanStages = computed(() => {
    const activeStages = Object.keys(props.stages).filter(s => !['won', 'lost'].includes(s));
    return activeStages.map(stageKey => ({
        key: stageKey,
        label: props.stages[stageKey],
        projects: props.projects[stageKey] || [],
        value: props.stageValues[stageKey] || 0,
    }));
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value);
};

const openProjectModal = (project = null) => {
    if (project) {
        editingProject.value = project;
        projectForm.value = {
            company_id: project.company_id,
            name: project.name,
            value: project.value,
            stage: project.stage,
            notes: project.notes || '',
            contact_ids: project.contacts.map(c => c.id),
        };
    } else {
        editingProject.value = null;
        projectForm.value = {
            company_id: null,
            name: '',
            value: 0,
            stage: 'lead',
            notes: '',
            contact_ids: [],
        };
    }
    showProjectModal.value = true;
};

const saveProject = () => {
    if (editingProject.value) {
        router.patch(route('crm.projects.update', editingProject.value.id), projectForm.value, {
            onSuccess: () => {
                showProjectModal.value = false;
            },
        });
    } else {
        router.post(route('crm.projects.store'), projectForm.value, {
            onSuccess: () => {
                showProjectModal.value = false;
            },
        });
    }
};

const saveCompany = () => {
    router.post(route('crm.companies.store'), companyForm.value, {
        onSuccess: () => {
            showCompanyModal.value = false;
            companyForm.value = { name: '', email: '', phone: '', notes: '' };
        },
    });
};

const saveContact = () => {
    router.post(route('crm.contacts.store'), contactForm.value, {
        onSuccess: () => {
            showContactModal.value = false;
            contactForm.value = { company_id: null, name: '', email: '', phone: '', title: '', notes: '' };
        },
    });
};

const onDragChange = (stageKey, evt) => {
    if (evt.added) {
        const project = evt.added.element;
        router.patch(route('crm.projects.stage', project.id), {
            stage: stageKey,
            position: evt.added.newIndex,
        }, {
            preserveScroll: true,
        });
    }
};

const deleteProject = (project) => {
    if (confirm('Are you sure you want to delete this project?')) {
        router.delete(route('crm.projects.destroy', project.id));
    }
};

const companyContacts = computed(() => {
    if (!projectForm.value.company_id) return [];
    return props.contacts.filter(c => c.company_id === projectForm.value.company_id);
});
</script>

<template>
    <AppLayout title="CRM">
        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-4 gap-4 mb-6">
                    <div v-for="(period, key) in stats" :key="key" class="bg-white rounded-lg shadow p-4">
                        <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">{{ key }}</h3>
                        <div class="flex justify-between">
                            <div>
                                <p class="text-xs text-gray-500">Won</p>
                                <p class="text-lg font-bold text-green-600">{{ formatCurrency(period.won) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Lost</p>
                                <p class="text-lg font-bold text-red-600">{{ formatCurrency(period.lost) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mb-4 flex gap-2">
                    <button @click="openProjectModal()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        + New Project
                    </button>
                    <button @click="showCompanyModal = true" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        + New Company
                    </button>
                    <button @click="showContactModal = true" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        + New Contact
                    </button>
                </div>

                <!-- Kanban Board -->
                <div class="flex gap-4 overflow-x-auto pb-4">
                    <div v-for="stage in kanbanStages" :key="stage.key" class="flex-shrink-0 w-80">
                        <div class="bg-gray-100 rounded-lg p-4">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="font-bold text-lg">{{ stage.label }}</h2>
                                <span class="text-sm font-semibold text-green-600">{{ formatCurrency(stage.value) }}</span>
                            </div>
                            
                            <draggable
                                v-model="stage.projects"
                                group="projects"
                                @change="onDragChange(stage.key, $event)"
                                item-key="id"
                                class="space-y-2 min-h-[200px]"
                            >
                                <template #item="{ element: project }">
                                    <div class="bg-white rounded shadow p-3 cursor-move hover:shadow-md transition">
                                        <div class="flex justify-between items-start mb-2">
                                            <h3 class="font-semibold text-sm">{{ project.name }}</h3>
                                            <div class="flex gap-1">
                                                <button @click="openProjectModal(project)" class="text-blue-500 text-xs hover:text-blue-700">
                                                    ✎
                                                </button>
                                                <button @click="deleteProject(project)" class="text-red-500 text-xs hover:text-red-700">
                                                    ×
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-600 mb-1">{{ project.company.name }}</p>
                                        <p class="text-sm font-bold text-green-600">{{ formatCurrency(project.value) }}</p>
                                        <div v-if="project.contacts.length" class="mt-2 text-xs text-gray-500">
                                            👤 {{ project.contacts.map(c => c.name).join(', ') }}
                                        </div>
                                        <p v-if="project.notes" class="mt-2 text-xs text-gray-600 italic">{{ project.notes }}</p>
                                    </div>
                                </template>
                            </draggable>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Modal -->
        <div v-if="showProjectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h2 class="text-xl font-bold mb-4">{{ editingProject ? 'Edit Project' : 'New Project' }}</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Company</label>
                        <select v-model="projectForm.company_id" class="w-full border rounded px-3 py-2" required>
                            <option :value="null">Select a company</option>
                            <option v-for="company in companies" :key="company.id" :value="company.id">
                                {{ company.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Project Name</label>
                        <input v-model="projectForm.name" type="text" class="w-full border rounded px-3 py-2" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Value</label>
                        <input v-model="projectForm.value" type="number" step="0.01" class="w-full border rounded px-3 py-2" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Stage</label>
                        <select v-model="projectForm.stage" class="w-full border rounded px-3 py-2">
                            <option v-for="(label, key) in stages" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>

                    <div v-if="companyContacts.length">
                        <label class="block text-sm font-medium mb-1">Contacts</label>
                        <div class="space-y-1">
                            <label v-for="contact in companyContacts" :key="contact.id" class="flex items-center">
                                <input type="checkbox" :value="contact.id" v-model="projectForm.contact_ids" class="mr-2">
                                {{ contact.name }}
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Notes</label>
                        <textarea v-model="projectForm.notes" class="w-full border rounded px-3 py-2" rows="3"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button @click="showProjectModal = false" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                        Cancel
                    </button>
                    <button @click="saveProject" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Save
                    </button>
                </div>
            </div>
        </div>

        <!-- Company Modal -->
        <div v-if="showCompanyModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h2 class="text-xl font-bold mb-4">New Company</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Company Name</label>
                        <input v-model="companyForm.name" type="text" class="w-full border rounded px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input v-model="companyForm.email" type="email" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Phone</label>
                        <input v-model="companyForm.phone" type="text" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Notes</label>
                        <textarea v-model="companyForm.notes" class="w-full border rounded px-3 py-2" rows="3"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button @click="showCompanyModal = false" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                        Cancel
                    </button>
                    <button @click="saveCompany" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Save
                    </button>
                </div>
            </div>
        </div>

        <!-- Contact Modal -->
        <div v-if="showContactModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h2 class="text-xl font-bold mb-4">New Contact</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Company</label>
                        <select v-model="contactForm.company_id" class="w-full border rounded px-3 py-2" required>
                            <option :value="null">Select a company</option>
                            <option v-for="company in companies" :key="company.id" :value="company.id">
                                {{ company.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Name</label>
                        <input v-model="contactForm.name" type="text" class="w-full border rounded px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Title</label>
                        <input v-model="contactForm.title" type="text" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input v-model="contactForm.email" type="email" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Phone</label>
                        <input v-model="contactForm.phone" type="text" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Notes</label>
                        <textarea v-model="contactForm.notes" class="w-full border rounded px-3 py-2" rows="3"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button @click="showContactModal = false" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                        Cancel
                    </button>
                    <button @click="saveContact" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Save
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>