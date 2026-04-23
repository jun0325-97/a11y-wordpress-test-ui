=== A11Y - AI Alt Text & Accessibility ===
Contributors: a11yso
Donate link: https://a11y.so/
Tags: accessibility, alt text, aria, screen reader, wcag
Requires PHP: 7.4
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 0.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Terms of use: https://a11y.so/terms

AI 기반으로 이미지 대체 텍스트(alt)와 상세 설명(aria-describedby)을 자동 생성하여 실질적인 웹 접근성을 구현합니다.

== Description ==

**A11Y**는 SEO가 아닌 접근성에 집중한 이미지 대체 텍스트 자동 생성 플러그인입니다.

스크린 리더 사용자, 저시력 사용자, 인지 장애 사용자 등 모든 방문자가 이미지 콘텐츠를 동등하게 경험할 수 있도록 돕습니다.

---

**자동 Alt 텍스트 생성**

이미지를 업로드하는 순간, AI가 이미지를 분석하여 간결하고 정확한 alt 텍스트를 자동으로 생성합니다. 단순 이미지(아이콘, 배너 등)와 복잡한 이미지(차트, 인포그래픽 등)를 구분하여 각각에 맞는 텍스트를 생성합니다.

**상세 설명(Long Description) 지원**

복잡한 이미지에 대해 `aria-describedby`로 연결되는 상세 설명을 별도로 생성합니다. 단순 alt 텍스트로는 전달하기 어려운 정보를 스크린 리더 사용자에게 완전하게 전달합니다.

**WCAG 2.2 기준 준수**

생성되는 alt 텍스트는 WCAG 2.2 Success Criterion 1.1.1(비텍스트 콘텐츠) 기준을 충족하도록 설계되었습니다. 장식용 이미지는 빈 alt(`alt=""`)로 처리하여 스크린 리더가 불필요하게 읽지 않도록 합니다.

**다국어 지원**

130개 이상의 언어로 alt 텍스트를 생성할 수 있으며, WPML 및 Polylang과 연동됩니다.

**일괄 생성(Bulk Generate)**

기존 미디어 라이브러리의 이미지에도 일괄로 alt 텍스트와 상세 설명을 생성할 수 있습니다.

**이력 관리**

처리된 이미지 목록과 생성된 텍스트를 History 페이지에서 확인하고 직접 수정할 수 있습니다.

**WP-CLI 지원**

`wp a11y generate` 명령으로 커맨드라인에서 alt 텍스트 생성을 자동화할 수 있습니다.

**페이지 빌더 호환**

Elementor, Divi, Beaver Builder, YOOtheme Pro 등 주요 페이지 빌더에서도 alt 텍스트가 정상적으로 반영됩니다.

---

> WARNING: 현재 버전(0.1.x)은 프로토타입 단계입니다.
> 목업 API 모드로 동작하며, 실제 이미지 분석은 수행되지 않습니다.
> 더미 데이터가 반환됩니다. 프로덕션 환경에서 사용하지 마세요.

== Installation ==

1. **방문** 플러그인 > 새로 추가
2. **검색** "A11Y Alt Text"
3. **설치 및 활성화**
4. **가입** https://a11y.so 에서 무료 계정 생성
5. **연결** 계정 > API Keys 에서 발급한 API 키를 플러그인 설정 페이지에 입력
6. **완료** 이제 이미지를 업로드하면 alt 텍스트가 자동으로 생성됩니다

== Frequently Asked Questions ==

= SEO 키워드 삽입 기능이 없나요? =

의도적으로 제외했습니다. A11Y는 검색엔진 최적화가 아닌 실제 접근성을 목적으로 합니다. alt 텍스트에 키워드를 억지로 삽입하는 것은 오히려 스크린 리더 사용자 경험을 해칩니다.

= aria-describedby는 어떻게 작동하나요? =

복잡한 이미지로 분류된 경우, 이미지 근처에 숨겨진 <div id="desc-{id}"> 요소가 삽입되고 이미지에 aria-describedby="desc-{id}" 속성이 추가됩니다. 스크린 리더는 이미지를 읽은 후 상세 설명도 읽어줍니다.

= 장식용 이미지는 어떻게 처리되나요? =

AI가 장식용으로 분류한 이미지는 alt=""(빈 문자열)로 설정합니다. 이는 WCAG 기준에 따른 올바른 처리 방식으로, 스크린 리더가 해당 이미지를 건너뜁니다.

= 기존 alt 텍스트가 있는 이미지도 덮어쓰나요? =

기본값은 '덮어쓰지 않음'입니다. Bulk Generate에서 "Overwrite existing alt text" 옵션을 켜면 기존 텍스트도 갱신할 수 있습니다.

= WPML / Polylang과 함께 사용할 수 있나요? =

네. 각 언어별로 별도의 alt 텍스트를 생성하며, Settings에서 생성할 언어를 선택할 수 있습니다.

자세한 내용은 https://a11y.so/support 를 참고하세요.

== Screenshots ==

1. 이미지 업로드 시 alt 텍스트와 상세 설명이 자동으로 생성됩니다.
2. 미디어 라이브러리 상세 화면에서 생성된 alt 텍스트와 aria-describedby 설명을 확인하고 수정할 수 있습니다.
3. 설정 페이지에서 API 키 연결, 언어 설정, 동작 옵션을 구성합니다.
4. Bulk Generate 도구로 기존 이미지 전체에 일괄 생성합니다.
5. History 페이지에서 처리 이력을 검토하고 텍스트를 직접 편집합니다.

== Upgrade Notice ==

= 0.1.0 =
초기 프로토타입 릴리스. 목업 API 모드로만 동작합니다.

== Changelog ==

= 0.1.0 - 2026-04-22 =
* 초기 프로토타입 릴리스
* 목업 API 모드 구현 (실제 API 미연동 상태에서 UI/UX 테스트 가능)
* 설정 페이지 UI 전면 개편 (워드프레스 네이티브 스타일 준수)
* 미디어 라이브러리 첨부파일 상세 화면 UI 개편
* Mock Mode 배너 추가 (테스트 모드 명시적 표시)
* 웰컴 패널 추가 (초기 설정 가이드)
* alt 텍스트 + 상세 설명(aria-describedby) 이중 구조 지원 준비