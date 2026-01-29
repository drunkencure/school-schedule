@extends('layouts.app')

@section('content')
    <div id="register-form"
         class="card"
         style="max-width: 600px; margin: 30px auto;"
         data-academies='@json($academies)'
         data-subjects='@json($allSubjects)'
         data-old-academy-ids='@json($selectedAcademyIds ?? [])'
         data-old-subject-ids='@json(old('subjects', []))'
         data-old-login-id="{{ old('login_id') }}"
         data-old-email="{{ old('email') }}"
         data-old-name="{{ old('name') }}">
        <h2>강사 등록 요청</h2>
        <form method="POST" action="{{ route('register.submit') }}" ref="formEl" @submit.prevent="onSubmit">
            @csrf
            <div class="form-group">
                <label for="login_id">아이디</label>
                <input type="text" id="login_id" name="login_id" v-model="loginId" v-bind="loginIdAttrs">
                <div class="text-error" v-if="submitCount > 0 && errors.login_id">[[ errors.login_id ]]</div>
            </div>
            <div class="form-group">
                <label for="email">이메일</label>
                <input type="email" id="email" name="email" v-model="emailValue" v-bind="emailAttrs">
                <div class="text-error" v-if="submitCount > 0 && errors.email">[[ errors.email ]]</div>
            </div>
            <div class="form-group">
                <label for="name">이름</label>
                <input type="text" id="name" name="name" v-model="nameValue" v-bind="nameAttrs">
                <div class="text-error" v-if="submitCount > 0 && errors.name">[[ errors.name ]]</div>
            </div>
            @if ($academies->isEmpty())
                <div class="alert alert-error">등록 가능한 학원이 없습니다. 관리자에게 문의하세요.</div>
            @else
                <div class="form-group">
                    <label>등록 학원 (복수 선택 가능)</label>
                    <div class="checkbox-group" id="academy-options">
                        <label v-for="academy in academies" :key="academy.id">
                            <input type="checkbox" name="academy_ids[]" :value="academy.id" v-model="academyIds">
                            [[ academy.name ]]
                        </label>
                    </div>
                    <div class="text-error" v-if="submitCount > 0 && errors.academy_ids">[[ errors.academy_ids ]]</div>
                </div>
            @endif
            <div class="form-group">
                <label>과목 선택 (복수 선택 가능)</label>
                @if ($academies->isEmpty())
                    <div class="alert alert-error">등록 가능한 학원이 없습니다. 관리자에게 문의하세요.</div>
                @elseif ($allSubjects->isEmpty())
                    <div class="alert alert-error">등록된 과목이 없습니다. 관리자에게 문의하세요.</div>
                @else
                    <div id="subject-empty" class="alert alert-error" v-if="academyIds.length && !hasVisibleSubjects">
                        선택한 학원에 등록된 과목이 없습니다. 관리자에게 문의하세요.
                    </div>
                    <div id="subjects-container">
                        <div class="subject-group" v-for="group in subjectGroups"
                             :key="group.academy.id"
                             :data-academy-id="group.academy.id"
                             v-show="group.visible">
                            <div class="text-muted" v-if="!group.subjects.length" style="margin-bottom: 12px;">
                                등록된 과목이 없습니다.
                            </div>
                            <div class="checkbox-group" v-else>
                                <label class="subject-option" v-for="subject in group.subjects" :key="subject.id">
                                    <input type="checkbox" name="subjects[]" :value="subject.id" v-model="subjectIds">
                                    [[ subject.name ]]
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="text-error" v-if="submitCount > 0 && errors.subjects">[[ errors.subjects ]]</div>
                @endif
            </div>
            <div class="form-group">
                <label for="password">비밀번호</label>
                <input type="password" id="password" name="password" v-model="password" v-bind="passwordAttrs">
                <div class="text-error" v-if="submitCount > 0 && errors.password">[[ errors.password ]]</div>
            </div>
            <div class="form-group">
                <label for="password_confirmation">비밀번호 확인</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       v-model="passwordConfirmation" v-bind="passwordConfirmationAttrs">
                <div class="text-error" v-if="submitCount > 0 && errors.password_confirmation">[[ errors.password_confirmation ]]</div>
            </div>
            <button type="submit" class="btn" :disabled="academies.length === 0 || !hasAnySubjects">
                등록 요청
            </button>
        </form>
    </div>
@endsection
