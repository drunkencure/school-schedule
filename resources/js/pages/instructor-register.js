import { computed, createApp, ref, watch } from 'vue';
import { configure, defineRule, useForm } from 'vee-validate';
import { confirmed, email, min, required } from '@vee-validate/rules';

defineRule('required', required);
defineRule('email', email);
defineRule('min', min);
defineRule('confirmed', confirmed);
defineRule('min_items', (value, [minItems]) => {
    if (!Array.isArray(value)) {
        return false;
    }

    return value.length >= Number(minItems)
        || `최소 ${minItems}개 이상 선택해야 합니다.`;
});

configure({
    generateMessage: (context) => {
        const fieldMessages = {
            login_id: '아이디를 입력해 주세요.',
            email: '이메일을 입력해 주세요.',
            name: '이름을 입력해 주세요.',
            password: '비밀번호를 입력해 주세요.',
            password_confirmation: '비밀번호 확인을 입력해 주세요.',
            academy_ids: '학원을 하나 이상 선택해야 합니다.',
            subjects: '과목을 하나 이상 선택해야 합니다.',
        };

        if (context.rule?.name === 'required' && fieldMessages[context.field]) {
            return fieldMessages[context.field];
        }

        if (context.rule?.name === 'min_items' && fieldMessages[context.field]) {
            return fieldMessages[context.field];
        }

        const messages = {
            required: '필수 입력 항목입니다.',
            email: '올바른 이메일 형식을 입력해 주세요.',
            min: `최소 ${context.rule?.params?.[0]}자 이상 입력해 주세요.`,
            confirmed: '비밀번호가 일치하지 않습니다.',
            min_items: '항목을 하나 이상 선택해야 합니다.',
        };

        return messages[context.rule?.name] || '입력값을 확인해 주세요.';
    },
});

const registerEl = document.getElementById('register-form');

if (registerEl) {
    const academies = JSON.parse(registerEl.dataset.academies || '[]');
    const subjects = JSON.parse(registerEl.dataset.subjects || '[]');
    const oldAcademyIds = JSON.parse(registerEl.dataset.oldAcademyIds || '[]')
        .map((id) => Number(id))
        .filter((id) => Number.isFinite(id));
    const oldSubjectIds = JSON.parse(registerEl.dataset.oldSubjectIds || '[]')
        .map((id) => Number(id))
        .filter((id) => Number.isFinite(id));

    const initialValues = {
        login_id: registerEl.dataset.oldLoginId || '',
        email: registerEl.dataset.oldEmail || '',
        name: registerEl.dataset.oldName || '',
        academy_ids: oldAcademyIds,
        subjects: oldSubjectIds,
        password: '',
        password_confirmation: '',
    };

    const app = createApp({
        setup() {
            const formEl = ref(null);
            const {
                handleSubmit,
                errors,
                submitCount,
                setFieldValue,
                defineField,
            } = useForm({
                initialValues,
                validationSchema: {
                    login_id: 'required',
                    email: 'required|email',
                    name: 'required',
                    academy_ids: 'required|min_items:1',
                    subjects: 'required|min_items:1',
                    password: 'required|min:6',
                    password_confirmation: 'required|confirmed:password',
                },
            });

            const [loginId, loginIdAttrs] = defineField('login_id');
            const [emailValue, emailAttrs] = defineField('email');
            const [nameValue, nameAttrs] = defineField('name');
            const [password, passwordAttrs] = defineField('password');
            const [passwordConfirmation, passwordConfirmationAttrs] = defineField('password_confirmation');
            const [academyIds] = defineField('academy_ids');
            const [subjectIds] = defineField('subjects');

            const selectedAcademyIds = computed(() => (academyIds.value || []).map((id) => Number(id)));

            const subjectGroups = computed(() => {
                return academies.map((academy) => {
                    const academySubjects = subjects.filter((subject) => subject.academy_id === academy.id);

                    return {
                        academy,
                        subjects: academySubjects,
                        visible: selectedAcademyIds.value.includes(academy.id),
                    };
                });
            });

            const visibleSubjects = computed(() => {
                return subjects.filter((subject) => selectedAcademyIds.value.includes(subject.academy_id));
            });

            const hasAnySubjects = computed(() => subjects.length > 0);
            const hasVisibleSubjects = computed(() => visibleSubjects.value.length > 0);

            watch(selectedAcademyIds, (nextAcademyIds) => {
                const allowedIds = new Set(
                    subjects
                        .filter((subject) => nextAcademyIds.includes(subject.academy_id))
                        .map((subject) => subject.id),
                );

                const nextSubjects = (subjectIds.value || [])
                    .map((id) => Number(id))
                    .filter((id) => allowedIds.has(id));

                if (nextSubjects.length !== (subjectIds.value || []).length) {
                    setFieldValue('subjects', nextSubjects);
                }
            });

            const onSubmit = handleSubmit(() => {
                if (formEl.value) {
                    formEl.value.submit();
                }
            });

            return {
                academies,
                errors,
                formEl,
                hasAnySubjects,
                hasVisibleSubjects,
                loginId,
                loginIdAttrs,
                emailValue,
                emailAttrs,
                nameValue,
                nameAttrs,
                password,
                passwordAttrs,
                passwordConfirmation,
                passwordConfirmationAttrs,
                academyIds,
                subjectIds,
                onSubmit,
                submitCount,
                subjectGroups,
            };
        },
    });
    app.config.compilerOptions.delimiters = ['[[', ']]'];
    app.mount(registerEl);
}
