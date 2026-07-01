<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import AuthenticationCard from "@/Components/AuthenticationCard.vue";
import AuthenticationCardLogo from "@/Components/AuthenticationCardLogo.vue";
import Checkbox from "@/Components/Checkbox.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        remember: form.remember ? "on" : "",
    })).post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Iniciar sesión" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div v-if="status" class="alert alert-success mb-3">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div class="mb-3">
                <InputLabel for="email" value="Correo Electrónico" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="w-100"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div class="mb-3">
                <InputLabel for="password" value="Contraseña" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="w-100"
                    required
                    autocomplete="current-password"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="form-check mb-3">
                <Checkbox
                    v-model:checked="form.remember"
                    name="remember"
                    id="remember"
                />
                <label class="form-check-label" for="remember">
                    Recordarme
                </label>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-decoration-none small"
                >
                    ¿Olvidaste tu contraseña?
                </Link>

                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Iniciar sesión
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard>
</template>
