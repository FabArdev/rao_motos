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
    usuario: Object,
});

const form = useForm({
    _method: "PUT",
    nombre: props.usuario.nombre,
    apellidos: props.usuario.apellidos,
    correo: props.usuario.correo,
    foto: null,
});

const enlaceVerificacionEnviado = ref(null);
const vistaPreviaFoto = ref(null);
const inputFoto = ref(null);

const actualizarInformacionPerfil = () => {
    if (inputFoto.value) {
        form.foto = inputFoto.value.files[0];
    }

    form.post(route("user-profile-information.update"), {
        errorBag: "updateProfileInformation",
        preserveScroll: true,
        onSuccess: () => limpiarInputFoto(),
    });
};

const enviarVerificacionCorreo = () => {
    enlaceVerificacionEnviado.value = true;
};

const seleccionarNuevaFoto = () => {
    inputFoto.value.click();
};

const actualizarVistaPreviaFoto = () => {
    const foto = inputFoto.value.files[0];

    if (!foto) return;

    const lector = new FileReader();

    lector.onload = (e) => {
        vistaPreviaFoto.value = e.target.result;
    };

    lector.readAsDataURL(foto);
};

const eliminarFoto = () => {
    router.delete(route("current-user-photo.destroy"), {
        preserveScroll: true,
        onSuccess: () => {
            vistaPreviaFoto.value = null;
            limpiarInputFoto();
        },
    });
};

const limpiarInputFoto = () => {
    if (inputFoto.value?.value) {
        inputFoto.value.value = null;
    }
};
</script>

<template>
    <FormSection @submitted="actualizarInformacionPerfil">
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
                    id="foto"
                    ref="inputFoto"
                    type="file"
                    class="d-none"
                    accept="image/png,image/jpeg,image/webp"
                    @change="actualizarVistaPreviaFoto"
                />

                <InputLabel for="foto" value="Foto de perfil" />

                <div style="display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem;">
                    <!-- Foto actual o vista previa de la nueva -->
                    <img
                        :src="vistaPreviaFoto || usuario.profile_photo_url"
                        :alt="usuario.nombre"
                        style="width: 80px; height: 80px; min-width: 80px; border-radius: 50%; object-fit: cover; border: 1px solid #dee2e6; display: block;"
                    />

                    <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: flex-start;">
                        <SecondaryButton
                            type="button"
                            @click.prevent="seleccionarNuevaFoto"
                        >
                            Seleccionar nueva foto
                        </SecondaryButton>

                        <SecondaryButton
                            v-if="usuario.profile_photo_path"
                            type="button"
                            @click.prevent="eliminarFoto"
                        >
                            Eliminar foto
                        </SecondaryButton>
                    </div>
                </div>

                <div class="form-text">JPG, PNG o WEBP, máximo 5 MB.</div>
                <InputError :message="form.errors.foto" class="mt-2" />
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

            <!-- Correo -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="correo" value="Correo Electrónico" />
                <TextInput
                    id="correo"
                    v-model="form.correo"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autocomplete="username"
                />
                <InputError :message="form.errors.correo" class="mt-2" />

                <div
                    v-if="
                        $page.props.jetstream.hasEmailVerification &&
                        usuario.correo_verificado_en === null
                    "
                >
                    <p class="text-sm mt-2">
                        Tu dirección de correo no está verificada.

                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="text-decoration-underline small text-muted"
                            @click.prevent="enviarVerificacionCorreo"
                        >
                            Haz clic aquí para reenviar el correo de verificación.
                        </Link>
                    </p>

                    <div
                        v-show="enlaceVerificacionEnviado"
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
