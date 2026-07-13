<script setup>
import { ref } from "vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
    user: Object,
});

const form = useForm({
    _method: "PUT",
    nombre: props.user.nombre,
    apellidos: props.user.apellidos,
    email: props.user.email,
    photo: null,
});

const verificationLinkSent = ref(null);
const photoPreview = ref(null);
const photoInput = ref(null);

const updateProfileInformation = () => {
    if (photoInput.value) {
        form.photo = photoInput.value.files[0];
    }

    form.post(route("user-profile-information.update"), {
        errorBag: "updateProfileInformation",
        preserveScroll: true,
        onSuccess: () => clearPhotoFileInput(),
    });
};

const sendEmailVerification = () => {
    verificationLinkSent.value = true;
};

const selectNewPhoto = () => {
    photoInput.value.click();
};

const updatePhotoPreview = () => {
    const photo = photoInput.value.files[0];

    if (!photo) return;

    const reader = new FileReader();

    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };

    reader.readAsDataURL(photo);
};

const deletePhoto = () => {
    router.delete(route("current-user-photo.destroy"), {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            clearPhotoFileInput();
        },
    });
};

const clearPhotoFileInput = () => {
    if (photoInput.value?.value) {
        photoInput.value.value = null;
    }
};
</script>

<template>
    <FormSection @submitted="updateProfileInformation">
        <template #title> Información del Perfil </template>

        <template #description>
            Actualiza la información de tu cuenta y dirección de correo
            electrónico.
        </template>

        <template #form>
            <!-- Foto de perfil -->
            <div
                v-if="$page.props.jetstream.managesProfilePhotos"
                class="mb-3"
            >
                <!-- Input de archivo oculto -->
                <input
                    id="photo"
                    ref="photoInput"
                    type="file"
                    class="d-none"
                    accept="image/png,image/jpeg,image/webp"
                    @change="updatePhotoPreview"
                />

                <InputLabel for="photo" value="Foto de perfil" />

                <div style="display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem;">
                    <!-- Foto actual o vista previa de la nueva -->
                    <img
                        :src="photoPreview || user.profile_photo_url"
                        :alt="user.nombre"
                        style="width: 80px; height: 80px; min-width: 80px; border-radius: 50%; object-fit: cover; border: 1px solid #dee2e6; display: block;"
                    />

                    <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: flex-start;">
                        <SecondaryButton
                            type="button"
                            @click.prevent="selectNewPhoto"
                        >
                            Seleccionar nueva foto
                        </SecondaryButton>

                        <SecondaryButton
                            v-if="user.profile_photo_path"
                            type="button"
                            @click.prevent="deletePhoto"
                        >
                            Eliminar foto
                        </SecondaryButton>
                    </div>
                </div>

                <div class="form-text">JPG, PNG o WEBP, máximo 5 MB.</div>
                <InputError :message="form.errors.photo" class="mt-2" />
            </div>

            <!-- Nombre -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="nombre" value="Nombre" />
                <TextInput
                    id="nombre"
                    v-model="form.nombre"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autocomplete="given-name"
                />
                <InputError :message="form.errors.nombre" class="mt-2" />
            </div>

            <!-- Apellidos -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="apellidos" value="Apellidos" />
                <TextInput
                    id="apellidos"
                    v-model="form.apellidos"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autocomplete="family-name"
                />
                <InputError :message="form.errors.apellidos" class="mt-2" />
            </div>

            <!-- Email -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="email" value="Correo Electrónico" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autocomplete="username"
                />
                <InputError :message="form.errors.email" class="mt-2" />

                <div
                    v-if="
                        $page.props.jetstream.hasEmailVerification &&
                        user.email_verified_at === null
                    "
                >
                    <p class="text-sm mt-2">
                        Tu dirección de correo no está verificada.

                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="text-decoration-underline small text-muted"
                            @click.prevent="sendEmailVerification"
                        >
                            Haz clic aquí para reenviar el correo de verificación.
                        </Link>
                    </p>

                    <div
                        v-show="verificationLinkSent"
                        class="mt-2 font-medium text-sm text-green-600"
                    >
                        Se ha enviado un nuevo enlace de verificación a tu
                        correo electrónico.
                    </div>
                </div>
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Guardado.
            </ActionMessage>

            <PrimaryButton
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Guardar
            </PrimaryButton>
        </template>
    </FormSection>
</template>
