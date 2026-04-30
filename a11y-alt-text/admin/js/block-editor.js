(function () {
  const { addFilter } = wp.hooks;
  const { createHigherOrderComponent } = wp.compose;
  const { InspectorControls } = wp.blockEditor;
  const { PanelBody, TextareaControl, Spinner, Notice } = wp.components;
  const { useSelect, useDispatch, subscribe } = wp.data;
  const { useState, useEffect, useRef, Fragment } = wp.element;
  const { __ } = wp.i18n;

  const withA11yDescriptionPanel = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
      if (props.name !== "core/image") {
        return wp.element.createElement(BlockEdit, props);
      }

      const attachmentId = props.attributes.id;

      // ─── 로컬 상태 ───────────────────────────────────────────────
      const [description, setDescription] = useState("");
      const [isSaving, setIsSaving] = useState(false);
      const [saveStatus, setSaveStatus] = useState(null); // 'saved' | 'error' | null
      const isSavingRef = useRef(false); // subscribe 클로저용 ref
      const [mounted, setMounted] = useState(false); // InspectorControls 등록 타이밍 제어용

      // ─── 1) 마운트 시 REST API로 현재 값 읽기 & 패널 등록 타이밍 제어 ────
      useEffect(() => {
        // mounted=true → InspectorControls 렌더 시작
        // native 블록 패널이 먼저 등록된 뒤 우리 패널이 등록되어 항상 Settings 아래에 위치
        setMounted(true);
      }, []);

      useEffect(() => {
        if (!attachmentId) return;

        wp.apiFetch({ path: `/wp/v2/media/${attachmentId}` })
          .then((media) => {
            setDescription(media?.meta?.a11y_description ?? "");
          })
          .catch(() => {});
      }, [attachmentId]);

      // ─── 2) 포스트 저장 시점 감지 → attachment meta 직접 저장 ────
      useEffect(() => {
        if (!attachmentId) return;

        const unsubscribe = subscribe(() => {
          const editor    = wp.data.select("core/editor");
          const nowSaving = editor.isSavingPost();

          if (nowSaving && !isSavingRef.current) {
            isSavingRef.current = true;
            setIsSaving(true);
            setSaveStatus(null);

            wp.apiFetch({
              path: `/wp/v2/media/${attachmentId}`,
              method: "POST",
              data: { meta: { a11y_description: description } },
            })
              .then(() => {
                setSaveStatus("saved");
                setTimeout(() => setSaveStatus(null), 2500);
              })
              .catch(() => {
                setSaveStatus("error");
              })
              .finally(() => {
                setIsSaving(false);
              });
          }

          if (!nowSaving) {
            isSavingRef.current = false;
          }
        });

        return () => unsubscribe();
      }, [attachmentId, description]);

      // ─── 렌더 ─────────────────────────────────────────────────────
      // BlockEdit를 먼저 렌더해 native 패널(Settings 등)이 slot에 먼저 등록되게 하고,
      // mounted=true 후에만 InspectorControls를 렌더해 항상 Settings 아래에 위치 고정
      return wp.element.createElement(
        Fragment,
        null,
        wp.element.createElement(BlockEdit, props),
        mounted && wp.element.createElement(
          InspectorControls,
          null,
          wp.element.createElement(
            PanelBody,
            {
              title: __("A11Y.so Description", "a11y-alt-text"),
              initialOpen: true,
            },

            // 이미지가 미디어 라이브러리 소속이 아닐 때 경고
            !attachmentId &&
              wp.element.createElement(
                Notice,
                { status: "warning", isDismissible: false },
                __(
                  "미디어 라이브러리에서 삽입한 이미지에만 저장됩니다.",
                  "a11y-alt-text"
                )
              ),

            // textarea
            wp.element.createElement(TextareaControl, {
              label: __("Description (aria-describedby)", "a11y-alt-text"),
              help: __(
                "복잡한 이미지의 상세 내용을 스크린 리더 사용자에게 전달합니다. 단순 이미지는 비워 두세요.",
                "a11y-alt-text"
              ),
              value: description,
              rows: 4,
              disabled: !attachmentId,
              onChange: (value) => {
                setDescription(value);
                setSaveStatus(null);
              },
            }),

            // 저장 중 스피너
            isSaving &&
              wp.element.createElement(
                "p",
                {
                  style: {
                    display: "flex",
                    alignItems: "center",
                    gap: "6px",
                    fontSize: "12px",
                    color: "#757575",
                    margin: "4px 0 0",
                  },
                },
                wp.element.createElement(Spinner),
                __("저장 중…", "a11y-alt-text")
              ),

            // 저장 성공 메시지
            saveStatus === "saved" &&
              wp.element.createElement(
                "p",
                {
                  style: {
                    fontSize: "12px",
                    color: "#1e7e34",
                    margin: "4px 0 0",
                  },
                },
                "✓ " + __("저장됐습니다.", "a11y-alt-text")
              ),

            // 저장 실패 메시지
            saveStatus === "error" &&
              wp.element.createElement(
                "p",
                {
                  style: {
                    fontSize: "12px",
                    color: "#cc1818",
                    margin: "4px 0 0",
                  },
                },
                "✕ " +
                  __(
                    "저장에 실패했습니다. 다시 시도해 주세요.",
                    "a11y-alt-text"
                  )
              )
          )
        )
      );
    };
  }, "withA11yDescriptionPanel");

  addFilter(
    "editor.BlockEdit",
    "a11y-alt-text/long-description-panel",
    withA11yDescriptionPanel
  );
})();
