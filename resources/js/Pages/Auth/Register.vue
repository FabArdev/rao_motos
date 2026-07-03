<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import AuthenticationCard from "@/Components/AuthenticationCard.vue";
import AuthenticationCardLogo from "@/Components/AuthenticationCardLogo.vue";
import Checkbox from "@/Components/Checkbox.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";

const form = useForm({
    nombre: "",
    apellidos: "",
    ci: "",
    telefono: "",
    direccion: "",
    email: "",
    fecha_nacimiento: "",
    password: "",
    password_confirmation: "",
    terms: false,
});

const submit = () => {
    form.post(route("register"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <Head title="Registro" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <form @submit.prevent="submit">
            <div class="mb-3">
                <InputLabel for="nombre" value="Nombre" />
                <TextInput
                    id="nombre"
                    v-model="form.nombre"
                    type="text"
                    class="w-100"
                    required
                    autofocus
                    autocomplete="given-name"
                />
                <InputError :message="form.errors.nombre" />
            </div>

            <div class="mb-3">
                <InputLabel for="apellidos" value="Apellidos" />
                <TextInput
                    id="apellidos"
                    v-model="form.apellidos"
                    type="text"
                    class="w-100"
                    required
                    autocomplete="family-name"
                />
                <InputError :message="form.errors.apellidos" />
            </div>

            <div class="mb-3">
                <InputLabel for="ci" value="CI" />
                <TextInput
                    id="ci"
                    v-model="form.ci"
                    type="text"
                    class="w-100"
                    required
                />
                <InputError :message="form.errors.ci" />
            </div>

            <div class="mb-3">
                <InputLabel for="telefono" value="Teléfono" />
                <TextInput
                    id="telefono"
                    v-model="form.telefono"
                    type="tel"
                    class="w-100"
                    required
                    autocomplete="tel"
                />
                <InputError :message="form.errors.telefono" />
            </div>

            <div class="mb-3">
                <InputLabel for="direccion" value="Dirección" />
                <TextInput
                    id="direccion"
                    v-model="form.direccion"
                    type="text"
                    class="w-100"
                    autocomplete="street-address"
                />
                <InputError :message="form.errors.direccion" />
            </div>

            <div class="mb-3">
                <InputLabel for="email" value="Correo Electrónico" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="w-100"
                    required
                    autocomplete="username"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div class="mb-3">
                <InputLabel
                    for="fecha_nacimiento"
                    value="Fecha de Nacimiento"
                />
                <TextInput
                    id="fecha_nacimiento"
                    v-model="form.fecha_nacimiento"
                    type="date"
                    class="w-100"
                    autocomplete="bday"
                />
                <InputError :message="form.errors.fecha_nacimiento" />
            </div>

            <div class="mb-3">
                <InputLabel for="password" value="Contraseña" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="w-100"
                    required
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="mb-3">
                <InputLabel
                    for="password_confirmation"
                    value="Confirmar Contraseña"
                />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="w-100"
                    required
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <div
                v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature"
                class="mb-3"
            >
                <div class="form-check">
                    <Checkbox
                        id="terms"
                        v-model:checked="form.terms"
                        name="terms"
                        required
                    />
                    <label class="form-check-label small" for="terms">
                        Acepto los
                        <a
                            target="_blank"
                            :href="route('terms.show')"
                            class="text-decoration-none"
                            >Términos del Servicio</a
                        >
                        y la
                        <a
                            target="_blank"
                            :href="route('policy.show')"
                            class="text-decoration-none"
                            >Política de Privacidad</a
                        >
                    </label>
                </div>
                <InputError :message="form.errors.terms" />
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <Link :href="route('login')" class="text-decoration-none small">
                    ¿Ya tienes cuenta?
                </Link>

                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Registrarse
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard>
</template>
