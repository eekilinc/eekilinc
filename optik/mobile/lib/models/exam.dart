/// Sınav listesi ve detayı (form-spec dahil).
class ExamSummary {
  ExamSummary({
    required this.id,
    required this.title,
    this.course,
    required this.questionCount,
    required this.optionCount,
    required this.booklets,
  });

  factory ExamSummary.fromJson(Map<String, dynamic> json) => ExamSummary(
        id: json['id'] as int,
        title: json['title'] as String,
        course: json['course'] as String?,
        questionCount: json['question_count'] as int,
        optionCount: json['option_count'] as int,
        booklets: (json['booklets'] as List).cast<String>(),
      );

  final int id;
  final String title;
  final String? course;
  final int questionCount;
  final int optionCount;
  final List<String> booklets;
}

/// GET /api/exams/{id} yanıtı: sınav + basılı formun geometrisi.
class ExamDetail {
  ExamDetail({
    required this.summary,
    required this.formVersion,
    required this.formSpec,
  });

  factory ExamDetail.fromJson(Map<String, dynamic> json) => ExamDetail(
        summary: ExamSummary(
          id: json['id'] as int,
          title: json['title'] as String,
          course: json['course'] as String?,
          questionCount: json['question_count'] as int,
          optionCount: json['option_count'] as int,
          booklets: (json['booklets'] as List).cast<String>(),
        ),
        formVersion: json['form_version'] as int,
        formSpec: FormSpec.fromJson(
          Map<String, dynamic>.from(json['form_spec'] as Map),
        ),
      );

  final ExamSummary summary;
  final int formVersion;
  final FormSpec formSpec;
}

/// Form geometrisi: API'den gelen spec JSON'unun tip güvenli hali.
class FormSpec {
  FormSpec({
    required this.questionCount,
    required this.optionCount,
    required this.options,
    required this.studentDigits,
    required this.bookletChoices,
    required this.fillThreshold,
    required this.studentZone,
    required this.bookletZone,
    required this.questionsZone,
    required this.questionsColumns,
    required this.questionsRowsPerColumn,
    required this.anchors,
  });

  factory FormSpec.fromJson(Map<String, dynamic> json) {
    List<double> zone(dynamic v) =>
        (v as List).map((e) => (e as num).toDouble()).toList();
    final questions = Map<String, dynamic>.from(json['questions'] as Map);
    final layout =
        Map<String, dynamic>.from(questions['layout'] as Map);
    final student = Map<String, dynamic>.from(json['student_no'] as Map);
    final booklet = Map<String, dynamic>.from(json['booklet'] as Map);
    final mark = Map<String, dynamic>.from(questions['mark'] as Map);
    // Köşe marker merkezleri (sayfa çerçevesinde, normalize).
    // Yoksa tüm çerçeve varsayılır.
    List<List<double>> anchors = const [
      [0.0, 0.0],
      [1.0, 0.0],
      [1.0, 1.0],
      [0.0, 1.0],
    ];
    final anchorsJson = json['anchors'];
    if (anchorsJson is Map && anchorsJson['positions'] is Map) {
      final pos =
          Map<String, dynamic>.from(anchorsJson['positions'] as Map);
      anchors = [
        zone(pos['tl']),
        zone(pos['tr']),
        zone(pos['br']),
        zone(pos['bl']),
      ];
    }
    return FormSpec(
      questionCount:
          (json['defaults'] as Map)['question_count'] as int? ?? 40,
      optionCount: (json['defaults'] as Map)['option_count'] as int? ?? 5,
      options: ((json['defaults'] as Map)['options'] as List).cast<String>(),
      studentDigits: student['digits'] as int? ?? 8,
      bookletChoices: (booklet['choices'] as List).cast<String>(),
      fillThreshold: (mark['fill_threshold'] as num?)?.toDouble() ?? 0.42,
      studentZone: zone(student['zone_norm']),
      bookletZone: zone(booklet['zone_norm']),
      questionsZone: zone(questions['zone_norm']),
      questionsColumns: layout['columns'] as int? ?? 2,
      questionsRowsPerColumn: layout['rows_per_column'] as int? ?? 20,
      anchors: anchors,
    );
  }

  final int questionCount;
  final int optionCount;
  final List<String> options;
  final int studentDigits;
  final List<String> bookletChoices;
  final double fillThreshold;
  final List<double> studentZone;
  final List<double> bookletZone;
  final List<double> questionsZone;
  final int questionsColumns;
  final int questionsRowsPerColumn;

  /// Köşe marker merkezleri, sayfa çerçevesinde normalize [tl, tr, br, bl].
  final List<List<double>> anchors;
}

/// Basılı formdaki QR içeriği: OPTIK1:{exam}:{version}:{booklet}:{checksum}.
class QrTarget {
  QrTarget({
    required this.examId,
    required this.version,
    required this.booklet,
    required this.raw,
  });

  static QrTarget? tryParse(String raw) {
    final parts = raw.trim().split(':');
    if (parts.length != 5 || parts[0] != 'OPTIK1') {
      return null;
    }
    final examId = int.tryParse(parts[1]);
    final version = int.tryParse(parts[2]);
    if (examId == null || version == null || parts[3].length != 1) {
      return null;
    }
    return QrTarget(
      examId: examId,
      version: version,
      booklet: parts[3].toUpperCase(),
      raw: raw,
    );
  }

  final int examId;
  final int version;
  final String booklet;
  final String raw;
}
